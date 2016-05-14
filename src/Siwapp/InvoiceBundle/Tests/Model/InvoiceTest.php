<?php

use Siwapp\InvoiceBundle\Entity\Invoice;
use Siwapp\InvoiceBundle\Entity\Item;
use Siwapp\CoreBundle\Tests\SiwappBaseTest;

class InvoiceTest extends SiwappBaseTest
{
    public function testTrial()
    {
        $test_invoice = $this->getRepo('invoice')->find(array('customer_name'=>'Smith and Co.', 'customer_identification'=>'2450626775P'));
        // test amounts calculation
        $test_invoice->setAmounts();
        $this->assertEquals($test_invoice->getBaseAmount(), 7198.85);
        $this->assertEquals($test_invoice->getNetAmount(), 7198.85);
        $this->assertEquals($test_invoice->getTaxAmount(), 1034.7995);
        $this->assertEquals($test_invoice->getGrossAmount(), 8233.65);

        // test tax_amount_<tax_name> property
        $this->assertEquals($test_invoice->tax_amount_iva16, 862.64);
        // checks post save ??
        $this->em->persist($test_invoice);
        $this->em->flush();
        $this->assertEquals($test_invoice->getBaseAmount(), 7198.85);
        $this->assertEquals($test_invoice->getNetAmount(), 7198.85);
        $this->assertEquals($test_invoice->getTaxAmount(), 1034.7995);
        $this->assertEquals($test_invoice->getGrossAmount(), 8233.65);

        // recalculate when deleting item
        $items = $test_invoice->getItems();
        $test_invoice->removeItem($items[2]);
        $this->em->flush();
        $this->assertEquals(count($test_invoice->getItems()), 4);
        $this->assertEquals($test_invoice->getGrossAmount(), 8072.36);
        // recalculate when adding item
        $new_item = new Item();
        $new_item->setDescription("test item");
        $new_item->setUnitaryCost(100);
        $new_item->setQuantity(1);
        $new_item->setDiscount(0);
        $this->em->persist($new_item);
        $test_invoice->addNewItem($new_item);
        $this->em->flush();
        $this->assertEquals($test_invoice->getGrossAmount(), 8172.36);
        // recalculate when updating item
        $item = $items[0];
        $item->setQuantity($item->getQuantity*2);
        $this->em->flush();
        $this->assertEquals($test_invoice->getGrossAmount(), 6903.01);

        // TODO: check number generation
        $internet_serie = $this->getRepo('serie')->findOneBy(array('name'=>'Internet'));
        $design_serie = $this->getRepo('serie')->findOneBy(array('name'=>'Design'));
        $others_serie = $this->getRepo('serie')->findOneBy(array('name'=>'Others'));
        $this->assertEquals($this->getRepo('invoice')->getNextNumber($internet_serie), 9);
        $this->assertEquals($this->getRepo('invoice')->getNextNumber($design_serie), 5);
        $this->assertEquals($this->getRepo('invoice')->getNextNumber($others_serie), 6);
        // TODO: check that when changing series, the number changes
        $test_invoice->setSerie($internet_serie);
        $this->em->flush();
        $this->assertEquals($test_invoice->getNumber(), 9);
        $test_invoice->setSerie($design_serie);
        $this->em->flush();
        $this->assertEquals($test_invoice->getNumber(), 5);

        // TODO: check savings with modified customer data
        

    }
}
