
# Welcome to the WebDriver Selenium PHP Project!

## This project were created to practice Laravel, Selenium and Data Extraction from PDF. 
These are a few things worked with in this project:
- Php WebDriver to work with Selenium
- Extraction of data from a PDF and save it into a CSV file
- Migrations
- Git!

**Prerequisites:**
- PHP 8.2
  

## Please follow the instructions below to start your project.

1) Download the repository

2) Open the terminal in the main directory of the project, then run  `composer install`

3) Create an .env file based on the .env.example and set up your DB connections. Also generate the key with the command: `php artisan key:generate`

4) Download the corresponding chromedriver at https://sites.google.com/chromium.org/driver/

5) Add the driver to the system variable path. It can be something like this: C:\ChromeDriver\bin

6) Download the selenium standalone driver at: https://www.selenium.dev/downloads/

7) Run `php artisan db:setup`. This command will create the database (if not exists), tables and initial data

8) Open one terminal and run `php artisan serve` 

9) Open another terminal, navite to the paste where the Selenium Standalone Driver is located and `java -jar selenium-server-XXXX.jar standalone`

10) Open the browser and go to your localhost artisan serve port to enjoy it! 
