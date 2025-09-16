<?php

namespace Oro\Bundle\ZendeskBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\ZendeskBundle\Provider\ChannelType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller to sync cases with Zendesk.
 */
class TicketController extends AbstractFOSRestController
{
    /**
     * @ApiDoc(
     *      description="Sync case with Zendesk",
     *      resource=true
     * )
     */
    #[QueryParam(name: 'id', requirements: '\d+', description: 'Case Id', nullable: false)]
    #[QueryParam(name: 'channelId', requirements: '\d+', description: 'Channel Id', nullable: false)]
    #[AclAncestor('orocrm_case_update')]
    public function postSyncCaseAction(
        #[MapEntity(id: 'id')]
        CaseEntity $caseEntity,
        #[MapEntity(id: 'channelId')]
        Channel $channel
    ) {
        if ($channel->getType() != ChannelType::TYPE) {
            return $this->handleView($this->view(['message' => 'Invalid channel type.'], Response::HTTP_BAD_REQUEST));
        }

        $this->container->get('oro_zendesk.model.sync_manager')->syncCase($caseEntity, $channel, true);

        return $this->handleView($this->view('', Response::HTTP_OK));
    }
}
