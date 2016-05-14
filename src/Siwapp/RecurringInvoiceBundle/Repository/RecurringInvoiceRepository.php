<?php

namespace Siwapp\RecurringInvoiceBundle\Repository;

use Doctrine\ORM\EntityRepository;

use Siwapp\CoreBundle\Repository\AbstractInvoiceRepository;

use Siwapp\RecurringInvoiceBundle\Entity\RecurringInvoice;

/**
 * RecurringInvoiceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RecurringInvoiceRepository extends AbstractInvoiceRepository
{
    /**
     * Returns the average incomes/day from all active recurring invoices
     *
     * @return float
     */
    public function getAverageDayAmount()
    {
        // Cant build this query with builder.
        $con = $this->getEntityManager()->getConnection();
        $smt = $con->prepare("SELECT SUM( ri.gross_amount / (ri.period*(CASE ri.period_type WHEN 'year' THEN 365 WHEN 'month' THEN 30 WHEN 'week' THEN 7 WHEN 'day' THEN 1 END))) AS average FROM recurring_invoice ri WHERE status IN(:active, :pending)");
        $smt->bindValue(':active', RecurringInvoice::ACTIVE, \PDO::PARAM_INT);
        $smt->bindValue(':pending', RecurringInvoice::PENDING, \PDO::PARAM_INT);
        $smt->execute();

        return $smt->fetchColumn();
    }
}
