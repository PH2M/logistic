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
namespace PH2M\Logistic\Model;

use PH2M\Logistic\Api\Data\LogInterface;
use PH2M\Logistic\Api\LogRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use PH2M\Logistic\Model\ResourceModel\Log as ResourceLog;

/**
 * Class LogRepository
 * @package PH2M\Logistic\Model
 */
class LogRepository implements LogRepositoryInterface
{
    /**
     * @var ResourceLog
     */
    protected $resource;

    /**
     * LogRepository constructor.
     * @param ResourceLog $resource
     */
    public function __construct(
        ResourceLog $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * @param LogInterface $log
     * @return LogInterface
     * @throws CouldNotSaveException
     */
    public function save(LogInterface $log)
    {
        try {
            $this->resource->save($log);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the log: %1', $exception->getMessage()),
                $exception
            );
        }
        return $log;
    }
}
