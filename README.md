About
==============
PHPSikuli is a PHP5 wrapper for [Sikuli](http://www.sikuli.org/).

Sikuli automates anything you see on the screen. It uses image recognition to identify and control GUI components.

Installation
==============
Sikuli is a Java application, that works on Windows XP+, Mac 10.6+ and most Linux/Unix systems. It requires [Java JRE 6 or JRE 7](http://www.java.com/en/download/manual.jsp) to be installed.

Clone PHPSikuli which includes Sikuli jar file:

```
git clone git@github.com:squizlabs/php-sikuli.git
```

Include either PHPSikuli.php or PHPSikuliBrowser.php in your project.

Documentation
==============
Sikuli documentation can be found [here](http://doc.sikuli.org/). Most wrapper methods have same or very similar names however their usage slightly differ. E.g:
```
// Python
topLeft = Location(reg.x, reg.y) # equivalent to
topLeft = reg.getTopLeft()

theWidth = reg.w # equivalent to
theWidth = reg.getW()

reg.h = theWidth # equivalent to
reg.setH(theWidth)

// PHP
$topLeft = $sikuli->createLocation($sikuli->getX($reg), $sikuli->getY($reg));
$topLeft = $sikuli->getTopLeft($reg);

$theWidth = $sikuli->getW($reg);

$sikuli->setH($reg);
```


Code Example
=============
```php
require_once 'PHPSikuli/PHPSikuliBrowser.php';

// Use Firefox.
$sikuli = new PHPSikuliBrowser('firefox');

// Go to Google.
$sikuli->goToURL('http://www.google.com');

// Search for 'Squiz Labs'
$sikuli->type('Squiz Labs');
$sikuli->keyDown('Key.ENTER');

sleep(1);

// Find the Squiz Labs Home page link on the search results page.
$link = $sikuli->find('Home');

// Highlight the found text (region) for 2 seconds.
$sikuli->highlight($link, 2);

// Click the link.
$sikuli->click($link);

```