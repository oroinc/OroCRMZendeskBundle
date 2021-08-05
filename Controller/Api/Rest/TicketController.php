<?php

namespace Oro\Bundle\ZendeskBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ZendeskBundle\Provider\ChannelType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller to sync cases with Zendesk.
 */
class TicketController extends AbstractFOSRestController
{
    /**
     * @ParamConverter("caseEntity", options={"id"="id"})
     * @ParamConverter("channel", options={"id"="channelId"})
     * @QueryParam(
     *      name="id",
     *      requirements="\d+",
     *      nullable=false,
     *      description="Case Id"
     * )
     * @QueryParam(
     *      name="channelId",
     *      requirements="\d+",
     *      nullable=false,
     *      description="Channel Id"
     * )
     * @ApiDoc(
     *      description="Sync case with Zendesk",
     *      resource=true
     * )
     * @AclAncestor("orocrm_case_update")
     */
    public function postSyncCaseAction(CaseEntity $caseEntity, Channel $channel)
    {
        if ($channel->getType() != ChannelType::TYPE) {
            return $this->handleView($this->view(['message' => 'Invalid channel type.'], Response::HTTP_BAD_REQUEST));
        }

        $this->get('oro_zendesk.model.sync_manager')->syncCase($caseEntity, $channel, true);

        return $this->handleView($this->view('', Response::HTTP_OK));
    }
}
