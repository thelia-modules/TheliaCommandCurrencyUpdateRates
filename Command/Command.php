<?php

namespace TheliaCommandCurrencyUpdateRates\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Thelia\Command\ContainerAwareCommand;
use Thelia\Core\Event\Currency\CurrencyUpdateRateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Currency;
use Thelia\Model\CurrencyQuery;
use Thelia\Model\Map\CurrencyI18nTableMap;

class Command extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("currency:rates:update")
            ->setDescription("Update currency rates");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->note('Please wait..');

        $event = new CurrencyUpdateRateEvent();

        $this->getDispatcher()->dispatch(TheliaEvents::CURRENCY_UPDATE_RATES, $event);

        $output->write(sprintf("\033\143"));


        /** @var Currency[] $currencies */
        $currencies = CurrencyQuery::create()
            ->useCurrencyI18nQuery(CurrencyI18nTableMap::TABLE_NAME)
            ->filterByLocale('en_US')
            ->endUse()
            ->withColumn(CurrencyI18nTableMap::NAME, 'name')
            ->find();

        $data = [];
        foreach ($currencies as $currency) {
            if (!$currency->getVisible()) {
                continue;
            }

            $data[] = [
                $currency->getVirtualColumn('name'),
                $currency->getSymbol(),
                $currency->getRate(),
                \in_array($currency->getId(), $event->getUndefinedRates())
                    ? '<error>this currency has not been updated</error>'
                    : '<info>this currency has been updated</info>'
            ];
        }

        $io->table([
            'Name',
            'Symbol',
            'Rate',
            'Info'
        ], $data);
    }
}
