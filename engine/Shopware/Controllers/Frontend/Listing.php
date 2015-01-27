<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

use Shopware\Bundle\SearchBundle\FacetResultInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\Product\Manufacturer;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;

/**
 * Listing controller
 *
 * @category  Shopware
 * @package   Shopware\Controllers\Frontend
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Frontend_Listing extends Enlight_Controller_Action
{
    /**
     * Listing of all manufacturer products.
     * Templates extends from the normal listing template.
     */
    public function manufacturerAction()
    {
        $this->get('query_alias_mapper')->replaceShortRequestQueries($this->Request());

        $manufacturerId = $this->Request()->getParam('sSupplier', null);

        /**@var $context ShopContextInterface*/
        $context = $this->get('shopware_storefront.context_service')->getShopContext();

        /**@var $criteria \Shopware\Bundle\SearchBundle\Criteria*/
        $criteria = $this->get('shopware_search.store_front_criteria_factory')
            ->createListingCriteria($this->Request(), $context);

        if ($criteria->hasCondition('manufacturer')) {
            $condition = $criteria->getCondition('manufacturer');
            $criteria->removeCondition('manufacturer');
            $criteria->addBaseCondition($condition);
        }

        $categoryArticles = Shopware()->Modules()->Articles()->sGetArticlesByCategory(
            $context->getShop()->getCategory()->getId(),
            $criteria
        );

        /**@var $manufacturer Manufacturer*/
        $manufacturer = $this->get('shopware_storefront.manufacturer_service')->get(
            $manufacturerId,
            $this->get('shopware_storefront.context_service')->getShopContext()
        );

        $facets = array();
        foreach ($categoryArticles['facets'] as $facet) {
            if (!$facet instanceof FacetResultInterface || $facet->getFacetName() == 'manufacturer') {
                continue;
            }
            $facets[] = $facet;
        }

        $categoryArticles['facets'] = $facets;

        $this->View()->assign($categoryArticles);
        $this->View()->assign('showListing', true);
        $this->View()->assign('manufacturer', $manufacturer);

        $this->View()->assign('sCategoryContent', $this->getSeoDataOfManufacturer($manufacturer));
    }

    /**
     * Index action method
     */
    public function indexAction()
    {
        /** @var $mapper \Shopware\Components\QueryAliasMapper */
        $mapper = $this->get('query_alias_mapper');
        $mapper->replaceShortRequestQueries($this->Request());

        $categoryId = $this->Request()->getParam('sCategory');

        if ($categoryId && !$this->isValidCategoryPath($categoryId)) {
            return $this->forward('index', 'index');
        }

        $categoryContent = Shopware()->Modules()->Categories()->sGetCategoryContent($categoryId);

        $categoryId = $categoryContent['id'];
        Shopware()->System()->_GET['sCategory'] = $categoryId;

        $location = $this->getRedirectLocation($categoryContent);
        if ($location) {
            return $this->redirect($location, array('code' => 301));
        }

        //check for seo information about the current manufacturer
        $seoSupplier = $this->get('config')->get('seoSupplier');
        $manufacturerId = $this->Request()->getParam('sSupplier', false);

        //old manufacturer listing
        if ($seoSupplier === true && $categoryContent['parentId'] == 1 && $manufacturerId) {

            /**@var $manufacturer Manufacturer*/
            $manufacturer = $this->get('shopware_storefront.manufacturer_service')->get(
                $manufacturerId,
                $this->get('shopware_storefront.context_service')->getShopContext()
            );

            $manufacturerContent = $this->getSeoDataOfManufacturer($manufacturer);

            $categoryContent = array_merge($categoryContent, $manufacturerContent);
        }

        $viewAssignments = array(
            'sBanner' => Shopware()->Modules()->Marketing()->sBanner($categoryId),
            'sBreadcrumb' => $this->getBreadcrumb($categoryId),
            'sCategoryInfo' => $categoryContent,
            'sCategoryContent' => $categoryContent,
            'campaigns' => $this->getCampaigns($categoryId),
            'activeFilterGroup' => $this->request->getQuery('sFilterGroup')
        );

        // fetch devices on responsive template or load full emotions for older templates.
        $templateVersion = Shopware()->Shop()->getTemplate()->getVersion();
        if ($templateVersion >= 3) {
            if ($this->Request()->getParam('sPage')) {
                $viewAssignments['hasEmotion'] = false;
            } else {
                $emotions = $this->get('emotion_device_configuration')->get($categoryId);
                $viewAssignments['emotions'] = $emotions;
                $viewAssignments['hasEmotion'] = (!empty($emotions));
            }

            $viewAssignments['showListing'] = (bool) max(array_column($emotions, 'showListing'));
        } else {
            //check category emotions
            $emotion = $this->getCategoryEmotion($categoryId);
            $viewAssignments['hasEmotion'] = !empty($emotion);
        }

        $showListing = (empty($emotion) || !empty($emotion['show_listing']));
        $viewAssignments['showListing'] = $showListing;

        if (!$showListing && $templateVersion < 3) {
            $this->View()->assign($viewAssignments);
            return;
        }

        $context = $this->get('shopware_storefront.context_service')->getShopContext();

        /**@var $criteria \Shopware\Bundle\SearchBundle\Criteria*/
        $criteria = $this->get('shopware_search.store_front_criteria_factory')
            ->createListingCriteria($this->Request(), $context);

        if ($categoryContent['hideFilter']) {
            $criteria->resetFacets();
        }

        if ($this->Request()->getParam('action') == 'manufacturer' && $criteria->hasCondition('manufacturer')) {
            $condition = $criteria->getCondition('manufacturer');
            $criteria->removeCondition('manufacturer');
            $criteria->addBaseCondition($condition);
        }

        $categoryArticles = Shopware()->Modules()->Articles()->sGetArticlesByCategory(
            $categoryId,
            $criteria
        );

        $template = $this->getCategoryTemplate($categoryContent, $categoryArticles);
        $categoryContent = array_merge($categoryContent, $template);

        if ($this->Request()->getParam('sRss') || $this->Request()->getParam('sAtom')) {
            $this->Response()->setHeader('Content-Type', 'text/xml');
            $type = $this->Request()->getParam('sRss') ? 'rss' : 'atom';
            $this->View()->loadTemplate('frontend/listing/' . $type . '.tpl');
        } elseif (!empty($categoryContent['template']) && empty($categoryContent['layout'])) {
            $this->view->loadTemplate('frontend/listing/' . $categoryContent['template']);
        }

        $viewAssignments['sCategoryContent'] = $categoryContent;

        $this->View()->assign($viewAssignments);
        $this->View()->assign($categoryArticles);
    }

    private function getRedirectLocation($categoryContent)
    {
        $location = false;

        if (!empty($categoryContent['external'])) {
            $location = $categoryContent['external'];
        } elseif (empty($categoryContent)) {
            $location = array('controller' => 'index');
        } elseif (Shopware()->Config()->categoryDetailLink && $categoryContent['articleCount'] == 1) {
            /**@var $repository \Shopware\Models\Category\Repository*/
            $repository = Shopware()->Models()->getRepository('Shopware\Models\Category\Category');
            $articleId = $repository->getActiveArticleIdByCategoryId($categoryContent['id']);

            if (!empty($articleId)) {
                $location = array(
                    'sViewport' => 'detail',
                    'sArticle' => $articleId
                );
            }
        } elseif ($this->isShopsBaseCategoryPage($categoryContent['id'])) {
            $location = array('controller' => 'index');
        }

        return $location;
    }


    /**
     * Converts the provided manufacturer to the category seo data structure.
     * Result can be merged with "sCategoryContent" to override relevant seo category data with
     * manufacturer data.
     *
     * @param Manufacturer $manufacturer
     * @return array
     */
    private function getSeoDataOfManufacturer(Manufacturer $manufacturer)
    {
        $content = array();

        $content['metaDescription'] = $manufacturer->getMetaDescription();
        $content['metaKeywords'] = $manufacturer->getMetaKeywords();

        $path = $this->Front()->Router()->assemble(array(
            'sViewport' => 'listing',
            'sAction'   => 'manufacturer',
            'sSupplier' => $manufacturer->getId(),
        ));

        if ($path) {
            $content['sSelfCanonical'] = $path;
        }

        if ($manufacturer->getMetaTitle()) {
            $content['title'] = $manufacturer->getMetaTitle() . ' | ' . $this->get('shop')->getName();
        } elseif ($manufacturer->getName()) {
            $content['title'] = $manufacturer->getName();
        }

        $content['canonicalTitle'] = $manufacturer->getName();

        return $content;
    }

    /**
     * @param $categoryId
     * @return array
     */
    private function getCampaigns($categoryId)
    {
        /**@var $repository \Shopware\Models\Emotion\Repository */
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Emotion\Emotion');

        $campaignsResult = $repository->getCampaignByCategoryQuery($categoryId)
            ->getArrayResult();

        $campaigns = array();
        foreach ($campaignsResult as $campaign) {
            $campaign['categoryId'] = $categoryId;
            $campaigns[$campaign['landingPageBlock']][] = $campaign;
        }
        return $campaigns;
    }

    /**
     * Returns a single emotion definition for the provided category id.
     *
     * @param $categoryId
     * @return array|mixed
     */
    private function getCategoryEmotion($categoryId)
    {
        if ($this->Request()->getQuery('sSupplier')
            || $this->Request()->getQuery('sPage')
            || $this->Request()->getQuery('sFilterProperties')
            || $this->Request()->getParam('sRss')
            || $this->Request()->getParam('sAtom')
        ) {
            return array();
        }

        $query = Shopware()->Container()->get('dbal_connection')->createQueryBuilder();

        $query->select(array('emotion.id', 'emotion.show_listing'))
            ->from('s_emotion', 'emotion')
            ->where('emotion.active = 1')
            ->andWhere('emotion.is_landingpage = 0')
            ->andWhere('(emotion.valid_to   >= NOW() OR emotion.valid_to IS NULL)')
            ->andWhere('(emotion.valid_from <= NOW() OR emotion.valid_from IS NULL)')
            ->setParameter(':categoryId', $categoryId);

        $query->innerJoin(
            'emotion',
            's_emotion_categories',
            'category',
            'category.emotion_id = emotion.id
             AND category.category_id = :categoryId'
        );

        /**@var $statement PDOStatement*/
        $statement = $query->execute();

        $data = $statement->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    private function getCategoryTemplate($categoryContent, $categoryArticles)
    {
        $template = array();
        if (empty($categoryContent['noViewSelect'])
            && !empty($categoryArticles['sTemplate'])
            && !empty($categoryContent['layout'])) {
            if ($categoryArticles['sTemplate'] == 'table') {
                if ($categoryContent['layout'] == '1col') {
                    $template['layout'] = '3col';
                    $template['template'] = 'article_listing_3col.tpl';
                }
            } else {
                $template['layout'] = '1col';
                $template['template'] = 'article_listing_1col.tpl';
            }
        }

        return $template;
    }

    /**
     * Helper function which checks the configuration for listing filters.
     * @return boolean
     */
    protected function displayFiltersInListing()
    {
        return Shopware()->Config()->get('displayFiltersInListings', true);
    }

    /**
     * Returns listing breadcrumb
     *
     * @param int $categoryId
     * @return array
     */
    public function getBreadcrumb($categoryId)
    {
        $breadcrumb = Shopware()->Modules()->Categories()->sGetCategoriesByParent($categoryId);
        return array_reverse($breadcrumb);
    }

    /**
     * Checks if the provided $categoryId is in the current shop's category tree
     *
     * @param int $categoryId
     * @return bool
     */
    private function isValidCategoryPath($categoryId)
    {
        $defaultShopCategoryId = Shopware()->Shop()->getCategory()->getId();

        /**@var $repository \Shopware\Models\Category\Repository*/
        $categoryRepository = Shopware()->Models()->getRepository('Shopware\Models\Category\Category');
        $categoryPath = $categoryRepository->getPathById($categoryId);

        if (array_shift(array_keys($categoryPath)) != $defaultShopCategoryId) {
            $this->Request()->setQuery('sCategory', $defaultShopCategoryId);

            $this->Response()->setHttpResponseCode(404);
            return false;
        }

        return true;
    }

    /**
     * Helper function used in the listing action to detect if
     * the user is trying to open the page matching the shop's root category
     *
     * @param $categoryId
     * @return bool
     */
    private function isShopsBaseCategoryPage($categoryId)
    {
        $defaultShopCategoryId = Shopware()->Shop()->getCategory()->getId();

        $queryParamsWhiteList = array('controller', 'action', 'sCategory');
        $queryParamsNames = array_keys($this->Request()->getParams());

        return ($defaultShopCategoryId == $categoryId && !array_diff($queryParamsNames, $queryParamsWhiteList));
    }
}
