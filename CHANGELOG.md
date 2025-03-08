# Yii Requirements Checker Change Log

## 1.1.1 under development

- Chg #86: Change PHP constraint in `composer.json` to `7.4.* || 8.0 - 8.4` (@vjik)
- Enh #86: Use FQN for built-in PHP functions (@vjik)
- Bug #86: Explicitly mark nullable parameters (@vjik)

## 1.1.0 November 19, 2024

- New #80: Add `RequirementsChecker::checkMaxExecutionTime()` that check on current server's php.ini's
  'max_execution_time' requiring it to exceed the time specified / tested application's comfortable installation
  time (@rossaddison)
- Enh #71: Improve requirements array validation (@vjik)
- Enh #78: Improve result appearance (@luizcmarin)
  
## 1.0.0 June 15, 2024

- Initial release.
