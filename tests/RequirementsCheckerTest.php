<?php

namespace Yiisoft\Requirements\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Requirements\RequirementsChecker;

class RequirementsCheckerTest extends TestCase
{
    public function testCheck(): void
    {
        $requirementsChecker = new RequirementsChecker();

        $requirements = [
            'requirementPass' => [
                'name' => 'Requirement 1',
                'mandatory' => true,
                'condition' => true,
                'by' => 'Requirement 1',
                'memo' => 'Requirement 1',
            ],
            'requirementError' => [
                'name' => 'Requirement 2',
                'mandatory' => true,
                'condition' => false,
                'by' => 'Requirement 2',
                'memo' => 'Requirement 2',
            ],
            'requirementWarning' => [
                'name' => 'Requirement 3',
                'mandatory' => false,
                'condition' => false,
                'by' => 'Requirement 3',
                'memo' => 'Requirement 3',
            ],
        ];

        $checkResult = $requirementsChecker
            ->check($requirements)
            ->getResult();
        $summary = $checkResult['summary'];

        $this->assertCount($summary['total'], $requirements, 'Wrong summary total!');
        $this->assertEquals(1, $summary['errors'], 'Wrong summary errors!');
        $this->assertEquals(1, $summary['warnings'], 'Wrong summary warnings!');

        $checkedRequirements = $checkResult['requirements'];
        $requirementsKeys = array_flip(array_keys($requirements));

        $this->assertFalse(
            $checkedRequirements[$requirementsKeys['requirementPass']]['error'],
            'Passed requirement has an error!'
        );
        $this->assertFalse(
            $checkedRequirements[$requirementsKeys['requirementPass']]['warning'],
            'Passed requirement has a warning!'
        );

        $this->assertTrue(
            $checkedRequirements[$requirementsKeys['requirementError']]['error'],
            'Error requirement has no error!'
        );

        $this->assertFalse(
            $checkedRequirements[$requirementsKeys['requirementWarning']]['error'],
            'Error requirement has an error!'
        );
        $this->assertTrue(
            $checkedRequirements[$requirementsKeys['requirementWarning']]['warning'],
            'Error requirement has no warning!'
        );
    }

    /**
     * @depends testCheck
     */
    public function testCheckEval(): void
    {
        $requirementsChecker = new RequirementsChecker();

        $requirements = [
            'requirementPass' => [
                'name' => 'Requirement 1',
                'mandatory' => true,
                'condition' => 'eval:2>1',
                'by' => 'Requirement 1',
                'memo' => 'Requirement 1',
            ],
            'requirementError' => [
                'name' => 'Requirement 2',
                'mandatory' => true,
                'condition' => 'eval:2<1',
                'by' => 'Requirement 2',
                'memo' => 'Requirement 2',
            ],
        ];

        $checkResult = $requirementsChecker
            ->check($requirements)
            ->getResult();
        $checkedRequirements = $checkResult['requirements'];
        $requirementsKeys = array_flip(array_keys($requirements));

        $this->assertFalse(
            $checkedRequirements[$requirementsKeys['requirementPass']]['error'],
            'Passed requirement has an error!'
        );
        $this->assertFalse(
            $checkedRequirements[$requirementsKeys['requirementPass']]['warning'],
            'Passed requirement has a warning!'
        );

        $this->assertTrue(
            $checkedRequirements[$requirementsKeys['requirementError']]['error'],
            'Error requirement has no error!'
        );
    }

    /**
     * @depends testCheck
     */
    public function testCheckChained(): void
    {
        $requirementsChecker = new RequirementsChecker();

        $requirements1 = [
            [
                'name' => 'Requirement 1',
                'mandatory' => true,
                'condition' => true,
                'by' => 'Requirement 1',
                'memo' => 'Requirement 1',
            ],
        ];
        $requirements2 = [
            [
                'name' => 'Requirement 2',
                'mandatory' => true,
                'condition' => true,
                'by' => 'Requirement 2',
                'memo' => 'Requirement 2',
            ],
        ];
        $checkResult = $requirementsChecker
            ->check($requirements1)
            ->check($requirements2)
            ->getResult();

        $mergedRequirements = array_merge($requirements1, $requirements2);

        $this->assertCount($checkResult['summary']['total'], $mergedRequirements, 'Wrong total checks count!');
        foreach ($mergedRequirements as $key => $mergedRequirement) {
            $this->assertEquals(
                $mergedRequirement['name'],
                $checkResult['requirements'][$key]['name'],
                'Wrong requirements list!'
            );
        }
    }

    public function testCheckPhpExtensionVersion(): void
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('Can not test this on HHVM.');
        }

        $requirementsChecker = new RequirementsChecker();

        $this->assertFalse(
            $requirementsChecker->checkPhpExtensionVersion('some_non_existing_php_extension', '0.1'),
            'No fail while checking non existing extension!'
        );
        $this->assertTrue($requirementsChecker->checkPhpExtensionVersion('pdo', '1.0'), 'Unable to check PDO version!');
    }

    /**
     * Data provider for [[testGetByteSize()]].
     * @return array
     */
    public function dataProviderGetByteSize(): array
    {
        return [
            ['456', 456],
            ['5K', 5 * 1024],
            ['16KB', 16 * 1024],
            ['4M', 4 * 1024 * 1024],
            ['14MB', 14 * 1024 * 1024],
            ['7G', 7 * 1024 * 1024 * 1024],
            ['12GB', 12 * 1024 * 1024 * 1024],
        ];
    }

    /**
     * @dataProvider dataProviderGetByteSize
     *
     * @param string $verboseValue Verbose value.
     * @param int $expectedByteSize Expected byte size.
     */
    public function testGetByteSize(string $verboseValue, int $expectedByteSize): void
    {
        $requirementsChecker = new RequirementsChecker();

        $this->assertEquals(
            $expectedByteSize,
            $requirementsChecker->getByteSize($verboseValue),
            "Wrong byte size for \"$verboseValue\"!"
        );
    }

    /**
     * Data provider for {@see testCompareByteSize()}
     * @return array
     */
    public function dataProviderCompareByteSize(): array
    {
        return [
            ['2M', '2K', '>', true],
            ['2M', '2K', '>=', true],
            ['1K', '1024', '==', true],
            ['10M', '11M', '<', true],
            ['10M', '11M', '<=', true],
        ];
    }

    /**
     * @depends testGetByteSize
     * @dataProvider dataProviderCompareByteSize
     *
     * @param string $a First value.
     * @param string $b Second value.
     * @param string $compare Comparison.
     * @param bool $expectedComparisonResult Expected comparison result.
     */
    public function testCompareByteSize(string $a, string $b, string $compare, bool $expectedComparisonResult): void
    {
        $requirementsChecker = new RequirementsChecker();
        $this->assertEquals(
            $expectedComparisonResult,
            $requirementsChecker->compareByteSize($a, $b, $compare),
            "Wrong compare \"{$a}{$compare}{$b}\""
        );
    }
}
