<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;

class WebDriverController extends Controller
{
    public function manageDriver()
    {
        // Configurações do driver
        $options = new ChromeOptions();
        $options->addArguments(['--disable-gpu']);
        // $options->setBinary(__DIR__ . '/bin/chromedriver.exe');
        $options->setBinary('C:\Program Files\Google\Chrome\Application\chrome.exe');

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        // Criação do driver
        $driver = RemoteWebDriver::create(
            'http://localhost:4444/wd/hub',
            $capabilities
        );

        // Navega para a página do Google
        $driver->get('https://testpages.herokuapp.com/styled/tag/table.html');

        // Captura todas as informações da tabela
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
        // Imprime as informações da tabela
        // echo "<pre>";print_r($data);
        echo "<pre>";
        foreach($data as $r)
            array_push($table_data, array('name' => $r[0], 'data' => $r[1]));

        print_r($table_data);

        // Fecha o navegador
        $driver->quit();
    }
}
