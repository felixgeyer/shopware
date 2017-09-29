<?php declare(strict_types=1);

namespace Shopware\Product\Factory;

use Doctrine\DBAL\Connection;
use Shopware\Context\Struct\TranslationContext;
use Shopware\CustomerGroup\Factory\CustomerGroupBasicFactory;
use Shopware\Framework\Factory\ExtensionRegistryInterface;
use Shopware\Framework\Factory\Factory;
use Shopware\PriceGroup\Factory\PriceGroupBasicFactory;
use Shopware\PriceGroup\Struct\PriceGroupBasicStruct;
use Shopware\Product\Extension\ProductExtension;
use Shopware\Product\Struct\ProductBasicStruct;
use Shopware\ProductDetail\Factory\ProductDetailBasicFactory;
use Shopware\ProductDetail\Struct\ProductDetailBasicStruct;
use Shopware\ProductListingPrice\Factory\ProductListingPriceBasicFactory;
use Shopware\ProductManufacturer\Factory\ProductManufacturerBasicFactory;
use Shopware\ProductManufacturer\Struct\ProductManufacturerBasicStruct;
use Shopware\Search\QueryBuilder;
use Shopware\Search\QuerySelection;
use Shopware\SeoUrl\Factory\SeoUrlBasicFactory;
use Shopware\SeoUrl\Struct\SeoUrlBasicStruct;
use Shopware\Tax\Factory\TaxBasicFactory;
use Shopware\Tax\Struct\TaxBasicStruct;

class ProductBasicFactory extends Factory
{
    const ROOT_NAME = 'product';
    const EXTENSION_NAMESPACE = 'product';

    const FIELDS = [
       'uuid' => 'uuid',
       'taxUuid' => 'tax_uuid',
       'manufacturerUuid' => 'product_manufacturer_uuid',
       'active' => 'active',
       'pseudoSales' => 'pseudo_sales',
       'markAsTopseller' => 'mark_as_topseller',
       'priceGroupUuid' => 'price_group_uuid',
       'filterGroupUuid' => 'filter_group_uuid',
       'isCloseout' => 'is_closeout',
       'allowNotification' => 'allow_notification',
       'template' => 'template',
       'configuratorSetId' => 'configurator_set_id',
       'createdAt' => 'created_at',
       'mainDetailUuid' => 'main_detail_uuid',
       'updatedAt' => 'updated_at',
       'name' => 'translation.name',
       'keywords' => 'translation.keywords',
       'description' => 'translation.description',
       'descriptionLong' => 'translation.description_long',
       'metaTitle' => 'translation.meta_title',
    ];

    /**
     * @var ProductManufacturerBasicFactory
     */
    protected $productManufacturerFactory;

    /**
     * @var ProductDetailBasicFactory
     */
    protected $productDetailFactory;

    /**
     * @var TaxBasicFactory
     */
    protected $taxFactory;

    /**
     * @var SeoUrlBasicFactory
     */
    protected $seoUrlFactory;

    /**
     * @var PriceGroupBasicFactory
     */
    protected $priceGroupFactory;

    /**
     * @var CustomerGroupBasicFactory
     */
    protected $customerGroupFactory;

    /**
     * @var ProductListingPriceBasicFactory
     */
    protected $productListingPriceFactory;

    public function __construct(
        Connection $connection,
        ExtensionRegistryInterface $registry,
        ProductManufacturerBasicFactory $productManufacturerFactory,
        ProductDetailBasicFactory $productDetailFactory,
        TaxBasicFactory $taxFactory,
        SeoUrlBasicFactory $seoUrlFactory,
        PriceGroupBasicFactory $priceGroupFactory,
        CustomerGroupBasicFactory $customerGroupFactory,
        ProductListingPriceBasicFactory $productListingPriceFactory
    ) {
        parent::__construct($connection, $registry);
        $this->productManufacturerFactory = $productManufacturerFactory;
        $this->productDetailFactory = $productDetailFactory;
        $this->taxFactory = $taxFactory;
        $this->seoUrlFactory = $seoUrlFactory;
        $this->priceGroupFactory = $priceGroupFactory;
        $this->customerGroupFactory = $customerGroupFactory;
        $this->productListingPriceFactory = $productListingPriceFactory;
    }

    public function hydrate(
        array $data,
        ProductBasicStruct $product,
        QuerySelection $selection,
        TranslationContext $context
    ): ProductBasicStruct {
        $product->setUuid((string) $data[$selection->getField('uuid')]);
        $product->setTaxUuid((string) $data[$selection->getField('taxUuid')]);
        $product->setManufacturerUuid((string) $data[$selection->getField('manufacturerUuid')]);
        $product->setActive((bool) $data[$selection->getField('active')]);
        $product->setPseudoSales((int) $data[$selection->getField('pseudoSales')]);
        $product->setMarkAsTopseller((bool) $data[$selection->getField('markAsTopseller')]);
        $product->setPriceGroupUuid(isset($data[$selection->getField('price_group_uuid')]) ? (string) $data[$selection->getField('priceGroupUuid')] : null);
        $product->setFilterGroupUuid(isset($data[$selection->getField('filter_group_uuid')]) ? (string) $data[$selection->getField('filterGroupUuid')] : null);
        $product->setIsCloseout((bool) $data[$selection->getField('isCloseout')]);
        $product->setAllowNotification((bool) $data[$selection->getField('allowNotification')]);
        $product->setTemplate(isset($data[$selection->getField('template')]) ? (string) $data[$selection->getField('template')] : null);
        $product->setConfiguratorSetId(isset($data[$selection->getField('configurator_set_id')]) ? (int) $data[$selection->getField('configuratorSetId')] : null);
        $product->setCreatedAt(isset($data[$selection->getField('created_at')]) ? new \DateTime($data[$selection->getField('createdAt')]) : null);
        $product->setMainDetailUuid((string) $data[$selection->getField('mainDetailUuid')]);
        $product->setUpdatedAt(isset($data[$selection->getField('updated_at')]) ? new \DateTime($data[$selection->getField('updatedAt')]) : null);
        $product->setName((string) $data[$selection->getField('name')]);
        $product->setKeywords(isset($data[$selection->getField('keywords')]) ? (string) $data[$selection->getField('keywords')] : null);
        $product->setDescription(isset($data[$selection->getField('description')]) ? (string) $data[$selection->getField('description')] : null);
        $product->setDescriptionLong(isset($data[$selection->getField('description_long')]) ? (string) $data[$selection->getField('descriptionLong')] : null);
        $product->setMetaTitle(isset($data[$selection->getField('meta_title')]) ? (string) $data[$selection->getField('metaTitle')] : null);
        $productManufacturer = $selection->filter('manufacturer');
        if ($productManufacturer && !empty($data[$productManufacturer->getField('uuid')])) {
            $product->setManufacturer(
                $this->productManufacturerFactory->hydrate($data, new ProductManufacturerBasicStruct(), $productManufacturer, $context)
            );
        }
        $productDetail = $selection->filter('mainDetail');
        if ($productDetail && !empty($data[$productDetail->getField('uuid')])) {
            $product->setMainDetail(
                $this->productDetailFactory->hydrate($data, new ProductDetailBasicStruct(), $productDetail, $context)
            );
        }
        $tax = $selection->filter('tax');
        if ($tax && !empty($data[$tax->getField('uuid')])) {
            $product->setTax(
                $this->taxFactory->hydrate($data, new TaxBasicStruct(), $tax, $context)
            );
        }
        $seoUrl = $selection->filter('canonicalUrl');
        if ($seoUrl && !empty($data[$seoUrl->getField('uuid')])) {
            $product->setCanonicalUrl(
                $this->seoUrlFactory->hydrate($data, new SeoUrlBasicStruct(), $seoUrl, $context)
            );
        }
        $priceGroup = $selection->filter('priceGroup');
        if ($priceGroup && !empty($data[$priceGroup->getField('uuid')])) {
            $product->setPriceGroup(
                $this->priceGroupFactory->hydrate($data, new PriceGroupBasicStruct(), $priceGroup, $context)
            );
        }
        if ($selection->hasField('_sub_select_blockedCustomerGroups_uuids')) {
            $uuids = explode('|', (string) $data[$selection->getField('_sub_select_blockedCustomerGroups_uuids')]);
            $product->setBlockedCustomerGroupsUuids(array_values(array_filter($uuids)));
        }

        /** @var $extension ProductExtension */
        foreach ($this->getExtensions() as $extension) {
            $extension->hydrate($product, $data, $selection, $context);
        }

        return $product;
    }

    public function getFields(): array
    {
        $fields = array_merge(self::FIELDS, parent::getFields());

        $fields['manufacturer'] = $this->productManufacturerFactory->getFields();
        $fields['mainDetail'] = $this->productDetailFactory->getFields();
        $fields['tax'] = $this->taxFactory->getFields();
        $fields['canonicalUrl'] = $this->seoUrlFactory->getFields();
        $fields['priceGroup'] = $this->priceGroupFactory->getFields();
        $fields['_sub_select_blockedCustomerGroups_uuids'] = '_sub_select_blockedCustomerGroups_uuids';

        return $fields;
    }

    public function joinDependencies(QuerySelection $selection, QueryBuilder $query, TranslationContext $context): void
    {
        $this->joinManufacturer($selection, $query, $context);
        $this->joinMainDetail($selection, $query, $context);
        $this->joinTax($selection, $query, $context);
        $this->joinCanonicalUrl($selection, $query, $context);
        $this->joinPriceGroup($selection, $query, $context);
        $this->joinBlockedCustomerGroups($selection, $query, $context);
        $this->joinListingPrices($selection, $query, $context);
        $this->joinTranslation($selection, $query, $context);

        $this->joinExtensionDependencies($selection, $query, $context);
    }

    public function getAllFields(): array
    {
        $fields = array_merge(self::FIELDS, $this->getExtensionFields());
        $fields['manufacturer'] = $this->productManufacturerFactory->getAllFields();
        $fields['mainDetail'] = $this->productDetailFactory->getAllFields();
        $fields['tax'] = $this->taxFactory->getAllFields();
        $fields['canonicalUrl'] = $this->seoUrlFactory->getAllFields();
        $fields['priceGroup'] = $this->priceGroupFactory->getAllFields();
        $fields['blockedCustomerGroups'] = $this->customerGroupFactory->getAllFields();
        $fields['listingPrices'] = $this->productListingPriceFactory->getAllFields();

        return $fields;
    }

    protected function getRootName(): string
    {
        return self::ROOT_NAME;
    }

    protected function getExtensionNamespace(): string
    {
        return self::EXTENSION_NAMESPACE;
    }

    private function joinManufacturer(
        QuerySelection $selection,
        QueryBuilder $query,
        TranslationContext $context
    ): void {
        if (!($productManufacturer = $selection->filter('manufacturer'))) {
            return;
        }
        $query->leftJoin(
            $selection->getRootEscaped(),
            'product_manufacturer',
            $productManufacturer->getRootEscaped(),
            sprintf('%s.uuid = %s.product_manufacturer_uuid', $productManufacturer->getRootEscaped(), $selection->getRootEscaped())
        );
        $this->productManufacturerFactory->joinDependencies($productManufacturer, $query, $context);
    }

    private function joinMainDetail(
        QuerySelection $selection,
        QueryBuilder $query,
        TranslationContext $context
    ): void {
        if (!($productDetail = $selection->filter('mainDetail'))) {
            return;
        }
        $query->leftJoin(
            $selection->getRootEscaped(),
            'product_detail',
            $productDetail->getRootEscaped(),
            sprintf('%s.uuid = %s.main_detail_uuid', $productDetail->getRootEscaped(), $selection->getRootEscaped())
        );
        $this->productDetailFactory->joinDependencies($productDetail, $query, $context);
    }

    private function joinTax(
        QuerySelection $selection,
        QueryBuilder $query,
        TranslationContext $context
    ): void {
        if (!($tax = $selection->filter('tax'))) {
            return;
        }
        $query->leftJoin(
            $selection->getRootEscaped(),
            'tax',
            $tax->getRootEscaped(),
            sprintf('%s.uuid = %s.tax_uuid', $tax->getRootEscaped(), $selection->getRootEscaped())
        );
        $this->taxFactory->joinDependencies($tax, $query, $context);
    }

    private function joinCanonicalUrl(
        QuerySelection $selection,
        QueryBuilder $query,
        TranslationContext $context
    ): void {
        if (!$canonical = $selection->filter('canonicalUrl')) {
            return;
        }
        $query->leftJoin(
            $selection->getRootEscaped(),
            'seo_url',
            $canonical->getRootEscaped(),
            sprintf('%1$s.uuid = %2$s.foreign_key AND %2$s.name = :productSeoName AND %2$s.is_canonical = 1 AND %2$s.shop_uuid = :shopUuid', $selection->getRootEscaped(), $canonical->getRootEscaped())
        );
        $query->setParameter('productSeoName', 'detail_page');
        $query->setParameter('shopUuid', $context->getShopUuid());
    }

    private function joinPriceGroup(
        QuerySelection $selection,
        QueryBuilder $query,
        TranslationContext $context
    ): void {
        if (!($priceGroup = $selection->filter('priceGroup'))) {
            return;
        }
        $query->leftJoin(
            $selection->getRootEscaped(),
            'price_group',
            $priceGroup->getRootEscaped(),
            sprintf('%s.uuid = %s.price_group_uuid', $priceGroup->getRootEscaped(), $selection->getRootEscaped())
        );
        $this->priceGroupFactory->joinDependencies($priceGroup, $query, $context);
    }

    private function joinBlockedCustomerGroups(
        QuerySelection $selection,
        QueryBuilder $query,
        TranslationContext $context
    ): void {
        if ($selection->hasField('_sub_select_blockedCustomerGroups_uuids')) {
            $query->addSelect('
                (
                    SELECT GROUP_CONCAT(mapping.customer_group_uuid SEPARATOR \'|\')
                    FROM product_avoid_customer_group mapping
                    WHERE mapping.product_uuid = ' . $selection->getRootEscaped() . '.uuid
                ) as ' . QuerySelection::escape($selection->getField('_sub_select_blockedCustomerGroups_uuids'))
            );
        }

        if (!($blockedCustomerGroups = $selection->filter('blockedCustomerGroups'))) {
            return;
        }

        $mapping = QuerySelection::escape($blockedCustomerGroups->getRoot() . '.mapping');

        $query->leftJoin(
            $selection->getRootEscaped(),
            'product_avoid_customer_group',
            $mapping,
            sprintf('%s.uuid = %s.product_uuid', $selection->getRootEscaped(), $mapping)
        );
        $query->leftJoin(
            $mapping,
            'customer_group',
            $blockedCustomerGroups->getRootEscaped(),
            sprintf('%s.customer_group_uuid = %s.uuid', $mapping, $blockedCustomerGroups->getRootEscaped())
        );

        $this->customerGroupFactory->joinDependencies($blockedCustomerGroups, $query, $context);

        $query->groupBy(sprintf('%s.uuid', $selection->getRootEscaped()));
    }

    private function joinListingPrices(
        QuerySelection $selection,
        QueryBuilder $query,
        TranslationContext $context
    ): void {
        if (!($listingPrices = $selection->filter('listingPrices'))) {
            return;
        }
        $query->leftJoin(
            $selection->getRootEscaped(),
            'product_listing_price_ro',
            $listingPrices->getRootEscaped(),
            sprintf('%s.uuid = %s.product_uuid', $selection->getRootEscaped(), $listingPrices->getRootEscaped())
        );

        $this->productListingPriceFactory->joinDependencies($listingPrices, $query, $context);

        $query->groupBy(sprintf('%s.uuid', $selection->getRootEscaped()));
    }

    private function joinTranslation(
        QuerySelection $selection,
        QueryBuilder $query,
        TranslationContext $context
    ): void {
        if (!($translation = $selection->filter('translation'))) {
            return;
        }
        $query->leftJoin(
            $selection->getRootEscaped(),
            'product_translation',
            $translation->getRootEscaped(),
            sprintf(
                '%s.product_uuid = %s.uuid AND %s.language_uuid = :languageUuid',
                $translation->getRootEscaped(),
                $selection->getRootEscaped(),
                $translation->getRootEscaped()
            )
        );
        $query->setParameter('languageUuid', $context->getShopUuid());
    }
}