<?php

namespace App\Http\Controllers;

use App\Models\HerokuappData;
use Illuminate\Http\Request;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;

class WebDriverController extends Controller
{
    public function manageDriver()
    {
        // Driver Settings
        $options = new ChromeOptions();
        $options->addArguments(['--disable-gpu']);
        // $options->setBinary(__DIR__ . '/bin/chromedriver.exe');
        $options->setBinary('C:\Program Files\Google\Chrome\Application\chrome.exe');

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        // Driver creation
        $driver = RemoteWebDriver::create(
            'http://localhost:4444/wd/hub',
            $capabilities
        );

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
        HerokuappData::insert($table_data);


        // Close the browser
        $driver->quit();
    }
}
