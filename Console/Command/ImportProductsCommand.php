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
namespace PH2M\Logistic\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use PH2M\Logistic\Model\Import\Product;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportProductsCommand
 * @package PH2M\Logistic\Console\Command
 */
class ImportProductsCommand extends Command
{
    /**
     * @var State
     */
    protected $appState;

    /**
     * @var Product
     */
    protected $productImport;

    /**
     * ImportProductsCommand constructor.
     * @param State $appState
     * @param Product $productImport
     */
    public function __construct(
        State $appState,
        Product $productImport
    ) {
        $this->appState         = $appState;
        $this->productImport    = $productImport;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('logistic:import:products')
            ->setDescription('Import products from logistic');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        $output->writeln("<comment>Begin products import...</comment>");

        try {
            $this->productImport->process();
            $output->writeln("<comment>Processing...</comment>");
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Cli::RETURN_FAILURE;
        }

        $output->write("\n");
        $output->writeln("<info>Product imported successfully</info>");
        return Cli::RETURN_SUCCESS;
    }
}
