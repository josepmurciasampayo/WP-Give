<?php

namespace Give\Donations\ListTable;

use Give\Donations\ListTable\Columns\AmountColumn;
use Give\Donations\ListTable\Columns\CreatedAtColumn;
use Give\Donations\ListTable\Columns\DonorColumn;
use Give\Donations\ListTable\Columns\FormColumn;
use Give\Donations\ListTable\Columns\GatewayColumn;
use Give\Donations\ListTable\Columns\IdColumn;
use Give\Donations\ListTable\Columns\PaymentTypeColumn;
use Give\Donations\ListTable\Columns\StatusColumn;
use Give\Framework\ListTable\ListTable;

/**
 * @unreleased
 */
class DonationsListTable extends ListTable
{
    /**
     * @unreleased
     *
     * @inheritDoc
     */
    public function id(): string
    {
        return 'donations';
    }

    /**
     * @unreleased
     *
     * @inheritDoc
     */
    public function getDefaultColumns(): array
    {
        return [
            new IdColumn(),
            new AmountColumn(),
            new PaymentTypeColumn(),
            new CreatedAtColumn(),
            new DonorColumn(),
            new FormColumn(),
            new GatewayColumn(),
            new StatusColumn(),
        ];
    }

    /**
     * @unreleased
     *
     * @inheritDoc
     */
    public function getDefaultVisibleColumns(): array
    {
        return [
            IdColumn::getId(),
            AmountColumn::getId(),
            PaymentTypeColumn::getId(),
            CreatedAtColumn::getId(),
            DonorColumn::getId(),
            FormColumn::getId(),
            StatusColumn::getId(),
        ];
    }
}
