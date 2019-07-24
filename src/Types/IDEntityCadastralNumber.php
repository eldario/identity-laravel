<?php

declare(strict_types = 1);

namespace AvtoDev\IDEntity\Types;

use Exception;
use AvtoDev\IDEntity\Helpers\CadastralNumberInfo;
use AvtoDev\StaticReferences\References\CadastralDistricts\CadastralRegions;
use AvtoDev\StaticReferences\References\CadastralDistricts\CadastralRegionEntry;
use AvtoDev\ExtendedLaravelValidator\Extensions\CadastralNumberValidatorExtension;

class IDEntityCadastralNumber extends AbstractTypedIDEntity implements HasCadastralNumberInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return static::ID_TYPE_CADASTRAL_NUMBER;
    }

    /**
     * {@inheritdoc}
     */
    public static function normalize($value): ?string
    {
        try {
            // Удаляем все символы, кроме разрешенных (цифры и знак ":")
            return (string) \preg_replace('~[^\d\:]~u', '', (string) $value);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Return parsed fragments of cadastral number.
     *
     * @return CadastralNumberInfo
     */
    public function getCadastralNumberInfo(): CadastralNumberInfo
    {
        return CadastralNumberInfo::parse($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function getRegionData(string $region_code): ?CadastralRegionEntry
    {
        static $districts = null;

        if (! $districts instanceof CadastralRegions) {
            $districts = new CadastralRegions;
        }

        return $districts->getRegionByCode($region_code);
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(): bool
    {
        /** @var CadastralNumberValidatorExtension $validator */
        $validator = static::getContainer()->make(CadastralNumberValidatorExtension::class);

        $validated = \is_string($this->value) && $validator->passes('', $this->value);

        $cadastral_number = $this->getCadastralNumberInfo();
        $region_data      = $this->getRegionData($cadastral_number->getRegionCode());

        return $validated
               && $region_data instanceof CadastralRegionEntry
               && $region_data->getDistricts()->hasDistrictCode($cadastral_number->getDistrictCode());
    }
}
