<?php

namespace Oro\Bundle\QueryDesignerBundle\Provider;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\Translator;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider as ParentEntityFieldProvider;
use Oro\Bundle\EntityBundle\Provider\ExcludeFieldProvider;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager as QueryDesignerManager;

class EntityFieldProvider extends ParentEntityFieldProvider
{
    /** @var QueryDesignerManager */
    protected $queryDesignerManager;

    /** @var string */
    protected $queryType;

    /** @var ExcludeFieldProvider */
    protected $excludeFieldProvider;

    public function __construct(
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider,
        EntityClassResolver $entityClassResolver,
        ManagerRegistry $doctrine,
        Translator $translator,
        VirtualFieldProviderInterface $virtualFieldProvider,
        ExcludeFieldProvider $excludeFieldProvider,
        $hiddenFields,
        QueryDesignerManager $qdManager
    ) {
        parent::__construct(
            $entityConfigProvider,
            $extendConfigProvider,
            $entityClassResolver,
            $doctrine,
            $translator,
            $virtualFieldProvider,
            $excludeFieldProvider,
            $hiddenFields
        );

        $this->excludeFieldProvider = $excludeFieldProvider;
        $this->queryDesignerManager = $qdManager;
    }

    /**
     * @param string $queryType
     */
    public function setQueryType($queryType)
    {
        $this->queryType = $queryType;
    }

    /**
     * {@inheritdoc}
     */
    protected function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        $result = parent::isIgnoredField($metadata, $fieldName);

        if (!$result) {
            $excludeRules = $this->queryDesignerManager->getExcludeRules();
            $result = $this->excludeFieldProvider->isIgnoreField($metadata, $fieldName, $excludeRules);
        }

        if (!$result) {
            $result = $this->isQueryTypeMatched($excludeRules);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        $result = parent::isIgnoredRelation($metadata, $associationName);

        if (!$result) {
            $excludeRules = $this->queryDesignerManager->getExcludeRules();
            $result = $this->excludeFieldProvider->isIgnoredRelation($metadata, $associationName, $excludeRules);
        }

        if (!$result) {
            $result = $this->isQueryTypeMatched($excludeRules);
        }

        return $result;
    }

    /**
     * @param array $excludeRules
     *
     * @return bool
     */
    protected function isQueryTypeMatched(array $excludeRules)
    {
        foreach ($excludeRules as $rule) {
            if (isset($rule['query_type']) && $rule['query_type'] === $this->queryType) {
                return true;
            }
        }

        return false;
    }
}
