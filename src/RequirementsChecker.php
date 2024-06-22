<?php

declare(strict_types=1);

namespace Yiisoft\Requirements;

use function intval;

/**
 * `RequirementsChecker` allows checking, if current system meets the requirements for running the Yii application.
 * This class allows rendering of the check report for the web and console application interface.
 *
 * Example:
 *
 * ```php
 * 
 * require_once('vendor/yiisoft/requirements/src/RequirementsChecker.php');
 * use Yiisoft\Requirements\RequirementsChecker;
 * $requirementsChecker = new RequirementsChecker;
 * $requirements = [
 *     [
 *         'name' => 'PHP Some Extension',
 *         'mandatory' => true`,
 *         'condition' => extension_loaded('some_extension'),
 *         'by' => 'Some application feature',
 *         'memo' => 'PHP extension "some_extension" required',
 *     ],
 * ];
 * $requirementsChecker
 *     ->check($requirements)
 *     ->render();
 * ```
 *
 * If you wish to render the report with your own representation, use {@see getResult()} instead of {@see render()}.
 *
 * Requirement condition could be in format "eval:PHP expression".
 * In this case specified PHP expression will be evaluated in the context of this class instance.
 * For example:
 *
 * ```php
 * $requirements = [
 *     [
 *         'name' => 'Upload max file size',
 *         'condition' => 'eval:$this->checkUploadMaxFileSize("5M")',
 *     ],
 * ];
 * ```
 */
final class RequirementsChecker
{
    /**
     * @var array|null The check results, this property is for internal usage only.
     *
     * @psalm-var array{
     *     summary: array{
     *       total: int,
     *       errors: int,
     *       warnings: int,
     *     },
     *     requirements: array
     * }|null
     */
    public ?array $result = null;

    /**
     * Check the given requirements, collecting results into internal field.
     * This method can be invoked several times checking different requirement sets.
     * Use {@see getResult()} or {@see render()} to get the results.
     * @param array|string $requirements Requirements to be checked.
     * If an array, it is treated as the set of requirements;
     * If a string, it is treated as the path of the file, which contains the requirements;
     * @return $this Self instance.
     */
    public function check($requirements): self
    {
        if (is_string($requirements)) {
            /** @psalm-suppress UnresolvableInclude */
            $requirements = require $requirements;
        }
        if (!is_array($requirements)) {
            $this->usageError('Requirements must be an array, "' . gettype($requirements) . '" has been given!');
        }
        if (!isset($this->result)) {
            $this->result = [
                'summary' => [
                    'total' => 0,
                    'errors' => 0,
                    'warnings' => 0,
                ],
                'requirements' => [],
            ];
        }
        foreach ($requirements as $key => $rawRequirement) {
            if (!is_array($rawRequirement)) {
                $this->usageError(
                    'Requirement must be an array, "' . gettype($rawRequirement) . '" has been given!'
                );
            }
            $requirement = $this->normalizeRequirement($rawRequirement, $key);
            $this->result['summary']['total']++;
            if (!$requirement['condition']) {
                if ($requirement['mandatory']) {
                    $requirement['error'] = true;
                    $requirement['warning'] = true;
                    $this->result['summary']['errors']++;
                } else {
                    $requirement['error'] = false;
                    $requirement['warning'] = true;
                    $this->result['summary']['warnings']++;
                }
            } else {
                $requirement['error'] = false;
                $requirement['warning'] = false;
            }
            $this->result['requirements'][] = $requirement;
        }

        return $this;
    }

    /**
     * Return the check results.
     * @return array|null check results in format:
     *
     * ```php
     * [
     *     'summary' => [
     *         'total' => total number of checks,
     *         'errors' => number of errors,
     *         'warnings' => number of warnings,
     *     ],
     *     'requirements' => [
     *         [
     *             ...
     *             'error' => is there an error,
     *             'warning' => is there a warning,
     *         ],
     *         ...
     *     ],
     * ]
     * ```
     */
    public function getResult(): ?array
    {
        return $this->result ?? null;
    }

    /**
     * Renders the requirements check result.
     * The output will vary depending on if a script running from web or from console.
     */
    public function render(): void
    {
        if (!isset($this->result)) {
            $this->usageError('Nothing to render!');
        }
        $baseViewFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'views';
        if (!empty($_SERVER['argv'])) {
            $viewFileName = $baseViewFilePath . DIRECTORY_SEPARATOR . 'console' . DIRECTORY_SEPARATOR . 'index.php';
        } else {
            $viewFileName = $baseViewFilePath . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'index.php';
        }
        $this->renderViewFile($viewFileName, $this->result);
    }

    /**
     * Checks if the given PHP extension is available and its version matches the given one.
     * @param string $extensionName PHP extension name.
     * @param string $version Required PHP extension version.
     * @param string $compare Comparison operator, by default '>='
     * @return bool If PHP extension version matches.
     *
     * @psalm-param '!='|'<'|'<='|'<>'|'='|'=='|'>'|'>='|'eq'|'ge'|'gt'|'le'|'lt'|'ne' $compare
     */
    public function checkPhpExtensionVersion(string $extensionName, string $version, string $compare = '>='): bool
    {
        if (!extension_loaded($extensionName)) {
            return false;
        }
        $extensionVersion = phpversion($extensionName);
        if (empty($extensionVersion)) {
            return false;
        }
        if (strncasecmp($extensionVersion, 'PECL-', 5) === 0) {
            $extensionVersion = substr($extensionVersion, 5);
        }

        /** @var bool */
        return version_compare($extensionVersion, $version, $compare);
    }

    /**
     * Checks if PHP configuration option (from `php.ini`) is on.
     * @param string $name Configuration option name.
     * @return bool Whether option is on.
     */
    public function checkPhpIniOn(string $name): bool
    {
        $value = ini_get($name);
        if (empty($value)) {
            return false;
        }

        return ((int) $value === 1 || strtolower($value) === 'on');
    }

    /**
     * Checks if PHP configuration option (from `php.ini`) is off.
     * @param string $name Configuration option name.
     * @return bool Whether option is off.
     */
    public function checkPhpIniOff(string $name): bool
    {
        $value = ini_get($name);
        if (empty($value)) {
            return true;
        }

        return (strtolower($value) === 'off');
    }

    /**
     * Compare byte sizes of values given in the verbose representation,
     * like '5M', '15K' etc.
     * @param string $a First value.
     * @param string $b Second value.
     * @param string $compare Comparison operator, by default '>='.
     * @return bool Comparison result.
     */
    public function compareByteSize(string $a, string $b, string $compare = '>='): bool
    {
        $compareExpression = '(' . $this->getByteSize($a) . $compare . $this->getByteSize($b) . ')';

        /** @var bool */
        return $this->evaluateExpression($compareExpression);
    }

    /**
     * Gets the size in bytes from verbose size representation.
     * For example: '5K' => 5*1024
     * @param string $verboseSize Verbose size representation.
     * @return int Actual size in bytes.
     */
    public function getByteSize(string $verboseSize): int
    {
        if (empty($verboseSize)) {
            return 0;
        }
        if (is_numeric($verboseSize)) {
            return (int) $verboseSize;
        }
        $sizeUnit = trim($verboseSize, '0123456789');
        $size = trim(str_replace($sizeUnit, '', $verboseSize));
        if (!is_numeric($size)) {
            return 0;
        }
        switch (strtolower($sizeUnit)) {
            case 'kb':
            case 'k':
                return intval($size * 1024);
            case 'mb':
            case 'm':
                return intval($size * 1024 * 1024);
            case 'gb':
            case 'g':
                return intval($size * 1024 * 1024 * 1024);
            default:
                return 0;
        }
    }

    /**
     * Checks if upload max file size matches the given range.
     * @param string|null $min Verbose file size minimum required value, pass null to skip minimum check.
     * @param string|null $max Verbose file size maximum required value, pass null to skip maximum check.
     * @return bool True on success.
     */
    public function checkUploadMaxFileSize(?string $min = null, ?string $max = null): bool
    {
        $postMaxSize = ini_get('post_max_size');
        $uploadMaxFileSize = ini_get('upload_max_filesize');
        if ($min !== null) {
            $minCheckResult = $this->compareByteSize($postMaxSize, $min, '>=') && $this->compareByteSize($uploadMaxFileSize, $min, '>=');
        } else {
            $minCheckResult = true;
        }
        if ($max !== null) {
            $maxCheckResult = $this->compareByteSize($postMaxSize, $max, '<=') && $this->compareByteSize($uploadMaxFileSize, $max, '<=');
        } else {
            $maxCheckResult = true;
        }

        return ($minCheckResult && $maxCheckResult);
    }

    /**
     * Renders a view file.
     * This method includes the view file as a PHP script
     * and captures the display result if required.
     * @param string $_viewFile_ View file.
     * @param array|null $_data_ Data to be extracted and made available to the view file.
     * @param bool $_return_ Whether the rendering result should be returned as a string.
     * @return string The rendering result. Null if the rendering result is not required.
     */
    public function renderViewFile(string $_viewFile_, array $_data_ = null, bool $_return_ = false): ?string
    {
        // we use special variable names here to avoid conflict when extracting data
        if (is_array($_data_)) {
            extract($_data_, EXTR_PREFIX_SAME, 'data');
        } else {
            $data = $_data_;
        }
        if ($_return_) {
            ob_start();
            /**
             * @psalm-suppress InvalidArgument Need for compatibility with PHP 7.4
             */
            PHP_VERSION_ID >= 80000 ? ob_implicit_flush(false) : ob_implicit_flush(0);

            /** @psalm-suppress UnresolvableInclude */
            require $_viewFile_;

            return ob_get_clean();
        }

        /** @psalm-suppress UnresolvableInclude */
        require $_viewFile_;

        return null;
    }

    /**
     * Normalizes requirement ensuring it has correct format.
     * @param array $requirement Raw requirement.
     * @param int|string $requirementKey Requirement key in the list.
     * @return array Normalized requirement.
     */
    public function normalizeRequirement(array $requirement, $requirementKey = 0): array
    {
        if (!array_key_exists('condition', $requirement)) {
            $this->usageError("Requirement \"$requirementKey\" has no condition!");
        } else {
            $evalPrefix = 'eval:';
            if (is_string($requirement['condition']) && strpos($requirement['condition'], $evalPrefix) === 0) {
                $expression = substr($requirement['condition'], strlen($evalPrefix));
                $requirement['condition'] = $this->evaluateExpression($expression);
            }
        }
        if (!array_key_exists('name', $requirement)) {
            $requirement['name'] = is_numeric($requirementKey) ? 'Requirement #' . $requirementKey : $requirementKey;
        }
        if (!array_key_exists('mandatory', $requirement)) {
            if (array_key_exists('required', $requirement)) {
                $requirement['mandatory'] = $requirement['required'];
            } else {
                $requirement['mandatory'] = false;
            }
        }
        if (!array_key_exists('by', $requirement)) {
            $requirement['by'] = 'Unknown';
        }
        if (!array_key_exists('memo', $requirement)) {
            $requirement['memo'] = '';
        }

        return $requirement;
    }

    /**
     * Displays a usage error.
     * This method will then terminate the execution of the current application.
     * @param string $message the error message
     *
     * @psalm-return never
     */
    public function usageError(string $message): void
    {
        echo "Error: $message\n\n";
        exit(1);
    }

    /**
     * Evaluates a PHP expression under the context of this class.
     * @param string $expression A PHP expression to be evaluated.
     * @return mixed The expression result.
     */
    public function evaluateExpression(string $expression)
    {
        return eval('return ' . $expression . ';');
    }

    /**
     * Returns the server information.
     * @return string server information.
     */
    public function getServerInfo(): string
    {
        return $_SERVER['SERVER_SOFTWARE'] ?? '';
    }

    /**
     * Returns the now date if possible in string representation.
     * @return string now date.
     */
    public function getNowDate(): string
    {
        return date("Y-m-d H:i:s", time());
    }
}
