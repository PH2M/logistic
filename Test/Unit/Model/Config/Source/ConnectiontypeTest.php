<?php
/**
 * 2011-2017 PH2M
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@ph2m.com so we can send you a copy immediately.
 *
 * @author PH2M - contact@ph2m.com
 * @copyright 2001-2017 PH2M
 * @license http://www.opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace PH2M\Logistic\Test\Unit\Model\Config\Source;

use Magento\Framework\Exception\ValidatorException;
use PH2M\Logistic\Model\Config\Source\Connectiontype;
use PHPUnit\Framework\TestCase;

/**
 * Class ConnectiontypeTest
 * @package PH2M\Logistic\Test\Unit\Model\Config\Source
 */
class ConnectiontypeTest extends TestCase
{
    /**
     * @var Connectiontype
     */
    protected $model;

    protected function setUp()
    {
        $this->model = new Connectiontype();
    }

    public function testPossibleTypesAreFtpAndSftp()
    {
        $possibleTypes = [
            [
                'value' => $this->model::CONNECTION_TYPE_FTP,
                'label' => 'FTP'
            ],
            [
                'value' => $this->model::CONNECTION_TYPE_SFTP,
                'label' => 'SFTP'
            ]
        ];

        $this->assertSame(json_encode($this->model->toOptionArray()), json_encode($possibleTypes));
    }

    public function testRightConnectionTypeShouldNotThrowAnyException()
    {
        $this->model->validateType($this->model::CONNECTION_TYPE_FTP);
        $this->model->validateType($this->model::CONNECTION_TYPE_SFTP);
    }

    public function testWrongConnectionTypeShouldThrowAnException()
    {
        $this->expectException(ValidatorException::class);
        $this->model->validateType('wrong_type');
    }
}