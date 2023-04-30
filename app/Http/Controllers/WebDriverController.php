<?php

namespace App\Http\Controllers;

use App\Models\HerokuappData;
use Illuminate\Http\Request;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Facades\Log;

class WebDriverController extends Controller
{

    private $chromeDriverPath;
    private $webDriverUrl;
    private $uploadFilePath;

    public function __construct()
    {
        $this->chromeDriverPath = env('CHROME_DRIVER_PATH', 'C:\Program Files\Google\Chrome\Application\chrome.exe');
        $this->webDriverUrl = env('WEB_DRIVER_URL', 'http://localhost:4444/wd/hub');
        $this->uploadFilePath = env('UPLOAD_FILE_PATH', '');
    }

    public function manageDriver()
    {
        $this->getDataFromSite();
        $this->fillForm();
    }

    private function createDriver()
    {
        // Driver Settings
        $options = new ChromeOptions();
        $options->addArguments(['--disable-gpu']);
        $options->setBinary($this->chromeDriverPath);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        // Driver creation
        $driver = RemoteWebDriver::create(
            $this->webDriverUrl,
            $capabilities
        );

        return $driver;
    }

    public function getDataFromSite() {
        try {
            $driver = $this->createDriver();

            // Navigate to a URL
            $driver->get('https://testpages.herokuapp.com/styled/tag/table.html');

            // Capture all the data from the table
            $table = $driver->findElement(WebDriverBy::id('mytable'));
            $rows = $table->findElements(WebDriverBy::tagName('tr'));
            $data = [];
            foreach (array_slice($rows, 1) as $row) {
                $cells = $row->findElements(WebDriverBy::tagName('td'));
                $rowData = [];
                foreach ($cells as $cell) {
                    $rowData[] = $cell->getText();
                }
                $data[] = $rowData;
            }

            $table_data = [];

            foreach($data as $r)
                array_push($table_data, array('name' => $r[0], 'data' => $r[1]));

            // Insert the data into the database
            HerokuappData::upsert($table_data, ['name', 'data']);

            // Close the browser
            $driver->quit();
        } catch (\Exception $e) {
            Log::error("[WebDriverController][getDataFromSite()][error: " . $e->getMessage() . "]");
        }
    }

    public function fillForm()
    {
        try {
            $driver = $this->createDriver();

            // Navigate to the form URL
            $driver->get('https://testpages.herokuapp.com/styled/basic-html-form-test.html');

            // Generate meaningful data
            $timestamp = time();
            $username = 'User-' . $timestamp;
            $password = 'Password-' . $timestamp;
            $comments = 'Commenting-' . $timestamp;
            $hiddenFieldValue = 'Hidden-' . $timestamp;

            // Find and fill form elements
            $driver->findElement(WebDriverBy::name('username'))->sendKeys($username);
            $driver->findElement(WebDriverBy::name('password'))->sendKeys($password);
            $driver->findElement(WebDriverBy::name('comments'))->clear()->sendKeys($comments);

            // Find and fill the hidden field
            $driver->executeScript("document.getElementsByName('hiddenField')[0].value = '$hiddenFieldValue';");

            // Upload the file
            $driver->findElement(WebDriverBy::name('filename'))->sendKeys($this->uploadFilePath);


            // Find and check the checkboxes
            $checkboxes = $driver->findElements(WebDriverBy::cssSelector('input[type="checkbox"][name="checkboxes[]"]'));
            foreach ($checkboxes as $checkbox) {
                $checkbox->click();
            }

            // Find and select radio buttons
            $radioButtons = $driver->findElements(WebDriverBy::cssSelector('input[type="radio"][name="radioval"]'));
            $radioButtons[array_rand($radioButtons)]->click();

            // Find and select multiple options
            $multipleSelect = $driver->findElement(WebDriverBy::name('multipleselect[]'));
            $options = $multipleSelect->findElements(WebDriverBy::tagName('option'));
            foreach ($options as $option) {
                if (rand(0, 1)) { // Randomly select options
                    $option->click();
                }
            }

            // Find and select a dropdown option
            $dropdown = $driver->findElement(WebDriverBy::name('dropdown'));
            $options = $dropdown->findElements(WebDriverBy::tagName('option'));
            $options[array_rand($options)]->click();

            // Sleep for 10 seconds to visualize the filled form
            sleep(5);

            // Submit the form
            // $driver->findElement(WebDriverBy::name('submitbutton'))->click();
            $driver->findElement(WebDriverBy::name('submitbutton'))->submit();

            // Sleep for 15 seconds to visualize the results
            sleep(15);

            // Close the browser
            $driver->quit();
        } catch (\Exception $e) {
            Log::error("[WebDriverController][fillForm()][error: " . $e->getMessage() . "]");
        }
    }


}
