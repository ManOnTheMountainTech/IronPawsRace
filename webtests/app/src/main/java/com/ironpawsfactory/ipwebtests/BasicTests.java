package com.ironpawsfactory.ipwebtests;

import org.openqa.selenium.WebDriver;
//import org.openqa.selenium.firefox.FirefoxDriver;
import org.openqa.selenium.chrome.ChromeDriver;
import org.openqa.selenium.remote.DesiredCapabilities;
import org.openqa.selenium.remote.RemoteWebDriver;

public class BasicTests {
    public static void main(String[] args) throws Exception {
        //WebDriver driver=new FirefoxDriver();
        WebDriver driver=new ChromeDriver();

        //WebDriver driver = new RemoteWebDriver("http://localhost:9515", DesiredCapabilities.);
        driver.get("http://www.monsterdogsports.com/ironpaws");
        Thread.sleep(3000);
        driver.quit();
    }
}
