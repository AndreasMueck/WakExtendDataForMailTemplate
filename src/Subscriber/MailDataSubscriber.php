<?php declare(strict_types=1);

namespace WakExtendDataForMailTemplate\Subscriber;

use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class MailDataSubscriber implements EventSubscriberInterface
{
    private EntityRepository $productRepository;

    public function __construct(EntityRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeLineItemAddedEvent::class => 'beforeLineItemAdded'
        ];
    }

    private function getProductById($productId, $context)
    {
        $product = $this->productRepository->search(new Criteria([$productId]), $context)->getEntities()->first();
        return $product;
    }

    public function beforeLineItemAdded(BeforeLineItemAddedEvent $event)
    {
        // liefert alle LineItems im Warenkorb
        //$cartLineItems = $event->getCart()->getLineItems(); // Shopware\Core\Checkout\Cart\LineItem\LineItemCollection
        
        // liefert neu hinzugefügtes LineItem im Warenkorb
        $lineItem = $event->getLineItem(); // Shopware\Core\Checkout\Cart\LineItem\LineItem
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $lineItem->getReferencedId()));
        // Mietmodul liefert eine eigene UUID: "43a1434b960243ca8a27b6b01ff354f8.rental.6c9f16594d419af762324544e4306ffe"
        // Daher wird als filter die "referencedId" genommen. Diese wird vom Mietmodul nicht verändert
        // ORIGINAL: $criteria->addFilter(new EqualsFilter('id', $lineItem->getId()));
        $criteria->setLimit(1);
        
        // Hole Produkt-Datensatz zum LineItem
        $products = $this->productRepository->search($criteria, $event->getContext()); // Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult
        
        

        foreach ($products as $product) // $product = Shopware\Core\Content\Product\ProductEntity
        { 
            // Checke packUnit-Felder für das LineItem
            if (($product->getPackUnit() !== null) && ($product->getPackUnitPlural() !== null)){
                $packUnits = array("packUnit"=>$product->getPackUnit(), "packUnitPlural"=>$product->getPackUnitPlural());
                $lineItem->setPayloadValue("packUnits", $packUnits);
            }
            // LineItem packUnit-Felder sind leer, checke ob ein Parent vorhanden ist und nimm daraus die packUnit-Felder
            elseif ($product->getParentId() !== null) {
                $product = $this->getProductById($product->getParentId(), $event->getContext());
                $packUnits = array("packUnit"=>$product->getPackUnit(), "packUnitPlural"=>$product->getPackUnitPlural());
                $lineItem->setPayloadValue("packUnits", $packUnits);
            }

            // Checke customFields des Produkts ob es ein Mietprodukt ist
            $productCustomFields = $product->getCustomFields();
            if (isset($productCustomFields['istMietProdukt'])) {
                //$lineItem->setPayloadValue("istMietProdukt", true);
                $isRentalProduct = array("istMietProdukt"=>true);
            }
            else {
                $isRentalProduct = array("istMietProdukt"=>false);
            }
            $lineItem->setPayloadValue("rentalProduct", $isRentalProduct);
        }
    }
}
