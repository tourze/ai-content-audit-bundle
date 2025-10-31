<?php

namespace AIContentAuditBundle\Exception;

/**
 * 服务未找到异常
 */
class ServiceNotFoundException extends \RuntimeException
{
    public function __construct(string $serviceClass, string $actualClass)
    {
        parent::__construct(sprintf(
            'Service "%s" is not an instance of "%s"',
            $actualClass,
            $serviceClass
        ));
    }
}
