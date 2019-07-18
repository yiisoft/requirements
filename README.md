<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii Requirements Checker</h1>
    <br>
</p>

The package allows to check if a certain set of defined requirements is met.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/requirements/v/stable.png)](https://packagist.org/packages/yiisoft/requirements)
[![Total Downloads](https://poser.pugx.org/yiisoft/requirements/downloads.png)](https://packagist.org/packages/yiisoft/requirements)
[![Build Status](https://travis-ci.com/yiisoft/requirements.svg?branch=master)](https://travis-ci.com/yiisoft/requirements)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/requirements/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/requirements/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/requirements/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/requirements/?branch=master)

## General usage

Requirements checker could be used either from web or from command line. Create `requirements.php`: 

```php
<?php
require_once('/path/to/requirements/RequirementsChecker.php');

$config = array(
    array(
        'name' => 'PHP version',
        'mandatory' => true,
        'condition' => version_compare(PHP_VERSION, '7.2.0', '>='),
        'by' => '<a href="http://www.yiiframework.com">Yii Framework</a>',
        'memo' => 'PHP 7.2.0 or higher is required.',
    ),
    array(
        'name' => 'PDO MySQL extension',
        'mandatory' => false,
        'condition' => extension_loaded('pdo_mysql'),
        'by' => 'All DB-related classes',
        'memo' => 'Required for MySQL database.',
    ),
    array(
        'name' => 'Intl extension',
        'mandatory' => false,
        'condition' => $this->checkPhpExtensionVersion('intl', '1.0.2', '>='),
        'by' => '<a href="https://secure.php.net/manual/en/book.intl.php">Internationalization</a> support',
        'memo' => 'PHP Intl extension 1.0.2 or higher is required.'
    ),    
);

$requirementsChecker = new RequirementsChecker();

$result = $requirementsChecker->checkYii()->check($config)->getResult();
$requirementsChecker->render();
exit($result['summary']['errors'] === 0 ? 0 : 1);
```

Now it could be either put to webroot or executed as `php requirements.php`.

Note that the code above uses PHP 4. That is done on purpose so the checker could be executed in a very old setups and
tell that upgrade should be done.
