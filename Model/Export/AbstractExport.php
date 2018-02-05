<?php
/**
 * 2011-2018 PH2M
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@ph2m.com so we can send you a copy immediately.
 *
 * @author PH2M - contact@ph2m.com
 * @copyright 2011-2018 PH2M
 * @license http://www.opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
namespace PH2M\Logistic\Model\Export;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\File\WriteFactory;
use Magento\Framework\Filesystem\Io\Ftp;
use Magento\Framework\Filesystem\Io\Sftp;
use PH2M\Logistic\Model\AbstractImportExport;
use PH2M\Logistic\Model\Config\Source\Connectiontype;
use PH2M\Logistic\Model\Log;
use PH2M\Logistic\Model\LogFactory;
use PH2M\Logistic\Api\LogRepositoryInterface;

/**
 * Class AbstractExport
 * @package PH2M\Logistic\Model\Export
 */
abstract class AbstractExport extends AbstractImportExport
{
    /**
     * Override this variable in your custom export with this format:
     * [
     *      'attribute_code_to_export' => 'column_name_in_file'
     * ]
     * @var array
     */
    protected $columnsToExport = [];

    /**
     * @var WriteFactory
     */
    protected $fileWriteFactory;

    /**
     * @var \Magento\Framework\Data\Collection\AbstractDb|array
     */
    protected $objectsToExport;

    /**
     * @var bool
     */
    protected $createAFileForEachObject = true;

    /**
     * AbstractExport constructor.
     * @param WriteFactory $fileWriteFactory
     * @param Ftp $ftp
     * @param Sftp $sftp
     * @param ScopeConfigInterface $scopeConfig
     * @param LogRepositoryInterface $logRepository
     * @param LogFactory $logFactory
     * @param Connectiontype $connectiontypeSource
     */
    public function __construct(
        WriteFactory $fileWriteFactory,
        Ftp $ftp,
        Sftp $sftp,
        ScopeConfigInterface $scopeConfig,
        LogRepositoryInterface $logRepository,
        LogFactory $logFactory,
        Connectiontype $connectiontypeSource
    ) {
        $this->fileWriteFactory = $fileWriteFactory;

        parent::__construct(
            $ftp,
            $sftp,
            $scopeConfig,
            $logRepository,
            $logFactory,
            $connectiontypeSource
        );
    }

    /**
     * @throws FileSystemException
     * @throws NotFoundException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function process()
    {
        $this->_initObjectsToExport();
        $this->_initConnection();
        $this->_exportObjects();
        $this->_reportResult();
    }

    /**
     * Override this function to create a collection or an array on objects to export
     */
    protected function _initObjectsToExport()
    {
        $this->objectsToExport = [];
    }

    /**
     * @throws NotFoundException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    protected function _initConnection()
    {
        parent::_initConnection();

        if (!$this->connection->cd($this->_getConfig('export', $this->code . '_path'))) {
            throw new NotFoundException(__('Export %1 path does not exist', $this->code));
        }
    }

    /**
     * @throws FileSystemException
     */
    protected function _exportObjects()
    {
        $header         = [];
        $content        = [];
        $nbColumns      = count($this->columnsToExport);
        $isFirstObject  = true;

        foreach ($this->objectsToExport as $object) {
            $line = [];

            if ($this->createAFileForEachObject || $isFirstObject) {
                $fileName = $this->_getFileName($object);
                $isFirstObject = false;
            }

            foreach ($this->columnsToExport as $attributeCode => $columnName) {
                if (count($header) < $nbColumns) {
                    // Header is not complete, add the current column
                    $header[] = $columnName;
                }

                $line[] = $object->getData($attributeCode);
            }

            $content[] = $line;

            if ($this->createAFileForEachObject) {
                $this->_createAndSendFile($fileName, $header, $content);
                $content = [];
            }
        }

        if (!$this->createAFileForEachObject) {
            $this->_createAndSendFile($fileName, $header, $content);
        }
    }

    /**
     * @param string $fileName
     * @param array|string $content
     * @param array $header
     * @throws FileSystemException
     *
     * $content must be an array of lines even if there is only one line
     */
    protected function _createAndSendFile($fileName, $header, $content)
    {
        $pathToSaveFiles = BP . DIRECTORY_SEPARATOR . DirectoryList::VAR_DIR . DIRECTORY_SEPARATOR . 'logistic' . DIRECTORY_SEPARATOR . $this->code;
        if (!is_dir($pathToSaveFiles)) {
            mkdir($pathToSaveFiles, 0777, true);
        }

        $filePath = $pathToSaveFiles . DIRECTORY_SEPARATOR . $fileName;

        $file = $this->fileWriteFactory->create($filePath, DriverPool::FILE, 'w');

        if ($file) {
            if (is_string($content)) {
                $file->write($content);
            } else {
                $file->writeCsv($header, $this->fieldSeparator, $this->fieldEnclosure);

                foreach ($content as $line) {
                    $file->writeCsv($line, $this->fieldSeparator, $this->fieldEnclosure);
                }
            }

            if ($this->connection->write($fileName, $filePath)) {
                $this->messages[] = 'File ' . $fileName . ' exported.';
            } else {
                $this->hasError = true;
                $this->messages[] = 'Error on exporting file ' . $fileName . '.';
            }
        }
    }

    /**
     * Override this function to set the export file name
     *
     * @param $object
     * @return string
     */
    protected function _getFileName($object)
    {
        return 'filename.csv';
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    protected function _reportResult()
    {
        if (count($this->messages)) {
            /** @var Log $log */
            $log = $this->logFactory->create();

            $log->setMessage(implode(' ', $this->messages))
                ->setEntityType($this->code);

            $this->logRepository->save($log);
        }
    }
}