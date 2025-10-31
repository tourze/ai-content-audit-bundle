<?php

namespace AIContentAuditBundle\Exception;

/**
 * 无效的 Repository 参数异常
 *
 * 当传递给 Repository 方法的参数无效时抛出此异常
 */
class InvalidRepositoryArgumentException extends \InvalidArgumentException
{
}
