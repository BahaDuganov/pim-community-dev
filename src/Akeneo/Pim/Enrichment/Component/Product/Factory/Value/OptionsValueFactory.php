<?php

namespace Akeneo\Pim\Enrichment\Component\Product\Factory\Value;

use Akeneo\Pim\Enrichment\Bundle\Sql\AttributeInterface;
use Akeneo\Pim\Enrichment\Component\Product\Exception\InvalidOptionsException;
use Akeneo\Tool\Component\StorageUtils\Exception\InvalidPropertyTypeException;
use Akeneo\Tool\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;

/**
 * Factory that creates options (multi-select) product values.
 *
 * @internal  Please, do not use this class directly. You must use \Akeneo\Pim\Enrichment\Component\Product\Factory\ValueFactory.
 *
 * @author    Damien Carcel (damien.carcel@akeneo.com)
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class OptionsValueFactory extends AbstractValueFactory
{
    /** @var IdentifiableObjectRepositoryInterface */
    protected $attrOptionRepository;

    public function __construct(
        IdentifiableObjectRepositoryInterface $attrOptionRepository,
        string $productValueClass,
        string $supportedAttributeType
    ) {
        parent::__construct($productValueClass, $supportedAttributeType);

        $this->attrOptionRepository = $attrOptionRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareData(AttributeInterface $attribute, $data, bool $ignoreUnknownData)
    {
        if (null === $data) {
            return [];
        }

        if (!is_array($data)) {
            throw InvalidPropertyTypeException::arrayExpected(
                $attribute->getCode(),
                static::class,
                $data
            );
        }

        foreach ($data as $value) {
            if (!is_string($value)) {
                throw InvalidPropertyTypeException::validArrayStructureExpected(
                    $attribute->getCode(),
                    sprintf('one of the options is not a string, "%s" given', gettype($value)),
                    static::class,
                    $data
                );
            }
        }

        sort($data);

        $notFoundOptionCodes = [];
        $foundOptionCodes = [];

        foreach ($data as $optionCode) {
            $identifier = $attribute->getCode() . '.' . $optionCode;
            $option = $this->attrOptionRepository->findOneByIdentifier($identifier);

            if (null === $option) {
                $notFoundOptionCodes[] = $optionCode;
            } else {
                $foundOptionCodes[] = $option->getCode();
            }
        }

        if (!empty($notFoundOptionCodes)) {
            throw InvalidOptionsException::validEntityListCodesExpected(
                $attribute->getCode(),
                'codes',
                'The options do not exist',
                static::class,
                $notFoundOptionCodes
            );
        }

        sort($foundOptionCodes);

        return $foundOptionCodes;
    }
}
