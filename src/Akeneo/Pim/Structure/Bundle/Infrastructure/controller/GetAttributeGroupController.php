<?php

declare(strict_types=1);

/**
 * @copyright 2023 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Akeneo\Pim\Structure\Bundle\Infrastructure\controller;

use Akeneo\Pim\Structure\Bundle\Application\GetAttributeGroup\GetAttributeGroupHandler;
use Akeneo\Platform\Bundle\FrameworkBundle\Security\SecurityFacadeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class GetAttributeGroupController
{
    public function __construct(
        private readonly SecurityFacadeInterface $securityFacade,
        private readonly GetAttributeGroupHandler $getAttributeGroupHandler
    ) {
    }

    public function __invoke(): Response
    {
        if (!$this->securityFacade->isGranted('pim_api_attribute_group_list')) {
            throw new AccessDeniedHttpException();
        }

        $attributeGroups = $this->getAttributeGroupHandler->handle();

        return new JsonResponse($attributeGroups);
    }
}