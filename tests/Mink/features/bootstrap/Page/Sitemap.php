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

namespace  Shopware\Tests\Mink\Page;

use SensioLabs\Behat\PageObjectExtension\PageObject\Page;
use Shopware\Tests\Mink\Element\SitemapGroup;
use Shopware\Tests\Mink\Helper;

class Sitemap extends Page
{
    /**
     * @var string
     */
    protected $path = '/sitemap{xml}';

    /**
     * {@inheritdoc}
     */
    public function open(array $urlParameters = ['xml' => ''])
    {
        return parent::open($urlParameters);
    }

    /**
     * @param SitemapGroup|string $group
     * @param string              $link
     * @param array               $sites
     *
     * @throws \Exception
     */
    public function checkGroup($group, $link, array $sites)
    {
        if (!($group instanceof SitemapGroup)) {
            $message = sprintf('Sitemap group "%s" was not found!', $group);
            Helper::throwException($message);
        }

        $data = Helper::getElementData($group, false);

        $this->checkGroupTitleLink($group->getText(), $link, $data['titleLink']);

        foreach ($sites as $site) {
            $level = 1;

            if (isset($site['level'])) {
                $level = $site['level'];
            }

            $this->checkGroupSite($site['value'], $site['link'], $data['level' . $level]);
        }
    }

    /**
     * @param array $links
     *
     * @throws \Exception
     */
    public function checkXml(array $links)
    {
        $homepageUrl = rtrim($this->getParameter('base_url'), '/');
        $xml = new \SimpleXMLElement($this->getContent());

        $check = [];
        $i = 0;

        foreach ($xml as $link) {
            if (empty($links[$i])) {
                $messages = [
                    'There are more links in the sitemap.xml as expected!',
                    sprintf('(%d sites in sitemap.xml, %d in test data', count($xml), count($links)),
                ];

                Helper::throwException($messages);
            }

            $check[] = [(string) $link->loc, $homepageUrl . $links[$i]['link']];
            ++$i;
        }

        $result = Helper::checkArray($check, true);

        if ($result === true) {
            return;
        }

        $messages = [
            'A link is different!',
            'Read: ' . $check[$result][0],
            'Expected: ' . $check[$result][1],
        ];

        Helper::throwException($messages);
    }

    /**
     * @param string $title
     * @param string $link
     * @param array  $data
     *
     * @throws \Exception
     */
    private function checkGroupTitleLink($title, $link, array $data)
    {
        $check = [
            'title' => [$data['title'], $title],
            'link' => [$data['link'],  $link],
        ];

        $result = Helper::checkArray($check);

        if ($result === true) {
            return;
        }

        if ($result === 'title') {
            $message = sprintf('Title of "%s" has a different value! (is "%s")', $check['title'][1], $check['title'][0]);
        } elseif (empty($link)) {
            $message = [
                sprintf('There is a link for the group "%s"!', $title),
                $check['link'][0],
            ];
        } else {
            $message = sprintf('The link of "%s" is different! ("%s" not found in "%s")', $title, $check['link'][1], $check['link'][0]);
        }

        Helper::throwException($message);
    }

    /**
     * @param string $title
     * @param string $link
     * @param array  $data
     *
     * @throws \Exception
     */
    private function checkGroupSite($title, $link, array $data)
    {
        foreach ($data as $site) {
            $check = [
                [$site['value'], $title],
                [$site['title'], $title],
                [$site['link'],  $link],
            ];

            $result = Helper::checkArray($check);

            if ($result === true) {
                return;
            }
        }

        $message = sprintf('The site "%s" with link "%s" was not found!', $title, $link);
        Helper::throwException($message);
    }
}
