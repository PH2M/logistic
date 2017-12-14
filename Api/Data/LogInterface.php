<?php
/**
 * 2011-2017 PH2M
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to agence@reflet-digital.com so we can send you a copy immediately.
 *
 * @author PH2M - contact@ph2m.com
 * @copyright 2001-2017 PH2M
 * @license http://www.opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
namespace PH2M\Logistic\Api\Data;

/**
 * Interface LogInterface
 * @package PH2M\Logistic\Api\Data
 */
interface LogInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const ENTITY_ID     = 'entity_id';
    const STATUS        = 'status';
    const ENTITY_TYPE   = 'entity_type';
    const MESSAGE       = 'message';
    const CREATED_AT    = 'created_at';
    /**#@-*/

    /**
     * Get ID
     *
     * @return int
     */
    public function getId();

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus();

    /**
     * Get entity type
     *
     * @return string
     */
    public function getEntityType();

    /**
     * Get message
     *
     * @return string|null
     */
    public function getMessage();

    /**
     * Get creation time
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set ID
     *
     * @param int $id
     * @return \PH2M\Logistic\Api\Data\LogInterface
     */
    public function setId($id);

    /**
     * Set status
     *
     * @param int $status
     * @return \PH2M\Logistic\Api\Data\LogInterface
     */
    public function setStatus($status);

    /**
     * Set entity type
     *
     * @param string $entityType
     * @return \PH2M\Logistic\Api\Data\LogInterface
     */
    public function setEntityType($entityType);

    /**
     * Set message
     *
     * @param string $message
     * @return \PH2M\Logistic\Api\Data\LogInterface
     */
    public function setMessage($message);

    /**
     * Set creation time
     *
     * @param string $createdAt
     * @return \PH2M\Logistic\Api\Data\LogInterface
     */
    public function setCreatedAt($createdAt);
}
