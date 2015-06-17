<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Manager;

use Pim\Bundle\CatalogBundle\Entity\Family;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Family mapping manager.
 *
 * @author    Olivier Soulet <olivier.soulet@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FamilyMappingManager
{
    /** @var ObjectManager */
    protected $objectManager;

    /** @var string */
    protected $className;

    /**
     * @param ObjectManager $objectManager
     * @param string        $className
     */
    public function __construct(ObjectManager $objectManager, $className)
    {
        $this->objectManager = $objectManager;
        $this->className     = $className;
    }

    /**
     * Get family from id and Prestashop url.
     *
     * @param integer $id
     * @param string  $prestashopUrl
     *
     * @return Family|null
     */
    public function getFamilyFromId($id, $prestashopUrl)
    {
        $prestashopFamilyMapping = $this->getEntityRepository()->findOneBy(
            [
                'prestashopFamilyId' => $id,
                'prestashopUrl'      => $prestashopUrl,
            ]
        );

        return $prestashopFamilyMapping ? $prestashopFamilyMapping->getFamily() : null;
    }

    /**
     * Get id from family and Prestashop url.
     *
     * @param Family $family
     * @param string $prestashopUrl
     *
     * @return integer|null
     */
    public function getIdFromFamily(Family $family, $prestashopUrl)
    {
        $familyMapping = $this->getEntityRepository()->findOneBy(
            [
                'family'     => $family,
                'prestashopUrl' => $prestashopUrl,
            ]
        );

        return $familyMapping ? $familyMapping->getPrestashopFamilyId() : null;
    }

    /**
     * Register a new family mapping.
     *
     * @param Family  $pimFamily
     * @param integer $prestashopFamilyId
     * @param string  $prestashopUrl
     */
    public function registerFamilyMapping(
        Family $pimFamily,
        $prestashopFamilyId,
        $prestashopUrl
    ) {
        $familyMapping = $this->getEntityRepository()->findOneBy([
            'family'     => $pimFamily,
            'prestashopUrl' => $prestashopUrl,
        ]);
        $prestashopFamilyMapping = new $this->className();

        if ($familyMapping) {
            $prestashopFamilyMapping = $familyMapping;
        }

        $prestashopFamilyMapping->setFamily($pimFamily);
        $prestashopFamilyMapping->setPrestashopFamilyId($prestashopFamilyId);
        $prestashopFamilyMapping->setPrestashopUrl($prestashopUrl);

        $this->objectManager->persist($prestashopFamilyMapping);
        $this->objectManager->flush();
    }

    /**
     * Does the given prestashop family exist in pim ?
     *
     * @param string $familyId
     * @param string $prestashopUrl
     *
     * @return boolean
     */
    public function prestashopFamilyExists($familyId, $prestashopUrl)
    {
        return null !== $this->getFamilyFromId($familyId, $prestashopUrl);
    }

    /**
     * Get the entity manager.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getEntityRepository()
    {
        return $this->objectManager->getRepository($this->className);
    }
}
