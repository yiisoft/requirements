<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Requirements Checker</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/requirements/v/stable.png)](https://packagist.org/packages/yiisoft/requirements)
[![Total Downloads](https://poser.pugx.org/yiisoft/requirements/downloads.png)](https://packagist.org/packages/yiisoft/requirements)
[![Build status](https://github.com/yiisoft/requirements/workflows/build/badge.svg)](https://github.com/yiisoft/requirements/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/requirements/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/requirements/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/requirements/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/requirements/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Frequirements%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/requirements/master)
[![static analysis](https://github.com/yiisoft/requirements/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/requirements/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/requirements/coverage.svg)](https://shepherd.dev/github/yiisoft/requirements)

The package allows to check if a certain set of defined requirements is met.

## Requirements

- PHP 7.4 or higher.

## General usage

Requirements checker could be used either from web or from command line. Create `requirements.php`: 

```php
<?php
require_once('/path/to/requirements/RequirementsChecker.php');

$config = array(
    array(
        'name' => 'PHP version',
        'mandatory' => true,
        'condition' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'by' => '<a href="http://www.yiiframework.com">Yii Framework</a>',
        'memo' => 'PHP 7.4.0 or higher is required.',
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

$result = $requirementsChecker
    ->checkYii()
    ->check($config)
    ->getResult();
$requirementsChecker->render();
exit($result['summary']['errors'] === 0 ? 0 : 1);
```

Now it could be either put to webroot or executed as `php requirements.php`.

> **Note** that the code above uses PHP 4. That is done on purpose so the checker could be executed in a very old setups and
>tell that upgrade should be done.

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Requirements Checker is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

### Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

### Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
