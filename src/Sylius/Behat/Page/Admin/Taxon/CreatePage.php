<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Behat\Page\Admin\Taxon;

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Sylius\Behat\Behaviour\SpecifiesItsCode;
use Sylius\Behat\Page\Admin\Crud\CreatePage as BaseCreatePage;
use Sylius\Component\Core\Model\TaxonInterface;
use Webmozart\Assert\Assert;

/**
 * @author Arkadiusz Krakowiak <arkadiusz.krakowiak@lakion.com>
 */
class CreatePage extends BaseCreatePage implements CreatePageInterface
{
    use SpecifiesItsCode;

    /**
     * {@inheritdoc}
     */
    public function countTaxons()
    {
        return count($this->getLeaves());
    }

    /**
     * {@inheritdoc}
     */
    public function countTaxonsByName($name)
    {
        $matchedLeavesCounter = 0;
        $leaves = $this->getLeaves();
        foreach ($leaves as $leaf) {
            if (strpos($leaf->getText(), $name) !== false) {
                $matchedLeavesCounter++;
            }
        }

        return $matchedLeavesCounter;
    }

    /**
     * {@inheritdoc}
     */
    public function chooseParent(TaxonInterface $taxon)
    {
        $this->getElement('parent')->selectOption($taxon->getName(), false);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTaxonOnPageByName($name)
    {
        $leaves = $this->getLeaves();
        foreach ($leaves as $leaf) {
            if ($leaf->getText() === $name) {
                $leaf->getParent()->find('css', '.ui.red.button')->press();

                return;
            }
        }

        throw new ElementNotFoundException($this->getDriver(), 'Delete button');
    }

    /**
     * {@inheritdoc}
     */
    public function describeItAs($description, $languageCode)
    {
        $this->getDocument()->fillField(sprintf('sylius_taxon_translations_%s_description', $languageCode), $description);
    }

    /**
     * {@inheritdoc}
     */
    public function hasTaxonWithName($name)
    {
        return 0 !== $this->countTaxonsByName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function nameIt($name, $languageCode)
    {
        $this->activateLanguageTab($languageCode);
        $this->getElement('name', ['%language%' => $languageCode])->setValue($name);

        $this->waitForSlugGenerationIfNecessary($languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function specifySlug($slug, $languageCode)
    {
        $this->getDocument()->fillField(sprintf('sylius_taxon_translations_%s_slug', $languageCode), $slug);
    }

    /**
     * {@inheritdoc}
     */
    public function attachImage($path, $code = null)
    {
        $filesPath = $this->getParameter('files_path');

        $this->getDocument()->find('css', '[data-form-collection="add"]')->click();

        $imageForm = $this->getLastImageElement();
        $imageForm->fillField('Code', $code);
        $imageForm->find('css', 'input[type="file"]')->attachFile($filesPath.$path);
    }

    /**
     * {@inheritDoc}
     */
    public function moveUp(TaxonInterface $taxon)
    {
        $this->moveLeaf($taxon, self::MOVE_DIRECTION_UP);
    }

    /**
     * {@inheritDoc}
     */
    public function moveDown(TaxonInterface $taxon)
    {
        $this->moveLeaf($taxon, self::MOVE_DIRECTION_DOWN);
    }

    /**
     * {@inheritDoc}
     */
    public function getLeafNameFromPosition($position, TaxonInterface $parentTaxon = null)
    {
        $firstTaxonElement = $this->getLeaves($parentTaxon)[0];

        return $firstTaxonElement->getText();
    }

    /**
     * {@inheritdoc}
     */
    public function getLeaves(TaxonInterface $parentTaxon = null)
    {
        $tree = $this->getElement('tree');
        Assert::notNull($tree);
        /** @var NodeElement[] $leaves */
        $leaves = $tree->findAll('css', '.item > .content > .header > a');

        if (null === $parentTaxon) {
            return $leaves;
        }

        foreach ($leaves as $leaf) {
            if ($leaf->getText() === $parentTaxon->getName()) {
                return $leaf->findAll('css', '.item > .content > .header');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function activateLanguageTab($locale)
    {
        if (!$this->getDriver() instanceof Selenium2Driver) {
            return;
        }

        $languageTabTitle = $this->getElement('language_tab', ['%locale%' => $locale]);
        if (!$languageTabTitle->hasClass('active')) {
            $languageTabTitle->click();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getElement($name, array $parameters = [])
    {
        if (!isset($parameters['%language%'])) {
            $parameters['%language%'] = 'en_US';
        }

        return parent::getElement($name, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefinedElements()
    {
        return array_merge(parent::getDefinedElements(), [
            'code' => '#sylius_taxon_code',
            'description' => '#sylius_taxon_translations_en_US_description',
            'images' => '#sylius_taxon_images',
            'language_tab' => '[data-locale="%locale%"] .title',
            'name' => '#sylius_taxon_translations_%language%_name',
            'parent' => '#sylius_taxon_parent',
            'slug' => '#sylius_taxon_translations_%language%_slug',
            'tree' => '.ui.list',
        ]);
    }

    /**
     * @param TaxonInterface $taxon
     * @param string $direction
     *
     * @throws ElementNotFoundException
     */
    private function moveLeaf(TaxonInterface $taxon, $direction)
    {
        Assert::oneOf($direction, [self::MOVE_DIRECTION_UP, self::MOVE_DIRECTION_DOWN]);

        $leaves = $this->getLeaves();
        foreach ($leaves as $leaf) {
            if ($leaf->getText() === $taxon->getName()) {
                $moveButton = $leaf->getParent()->find('css', sprintf('.sylius-taxon-move-%s', $direction));
                $moveButton->click();

                $moveButton->waitFor(5, function () use ($moveButton) {
                    return $this->isOpen() && !$moveButton->hasClass('loading');
                });

                return;
            }
        }

        throw new ElementNotFoundException(
            $this->getDriver(),
            sprintf('Move %s button for %s taxon', $direction, $taxon->getName())
        );
    }

    /**
     * @return NodeElement
     */
    private function getLastImageElement()
    {
        $images = $this->getElement('images');
        $items = $images->findAll('css', 'div[data-form-collection="item"]');

        Assert::notEmpty($items);

        return end($items);
    }

    /**
     * @param string $languageCode
     */
    private function waitForSlugGenerationIfNecessary($languageCode)
    {
        if ($this->getDriver() instanceof Selenium2Driver) {
            $this->getDocument()->waitFor(10, function () use ($languageCode) {
                return '' !== $this->getElement('slug', ['%language%' => $languageCode])->getValue();
            });
        }
    }
}
