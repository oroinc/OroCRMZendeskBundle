<?php

namespace OroCRM\Bundle\ZendeskBundle\Controller\Api\Rest;

use OroCRM\Bundle\ZendeskBundle\Provider\ChannelType;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;

/**
 * @Rest\RouteResource("ticket")
 * @Rest\NamePrefix("oro_api_")
 */
class TicketController extends FOSRestController
{
    /**
     * @Rest\Post(
     *      "/ticket/sync/case/{id}/channel/{channelId}",
     *      requirements={"id"="\d+", "channelId"="\d+"}
     * )
     * @ParamConverter("caseEntity", options={"id"="id"})
     * @ParamConverter("channel", options={"id"="channelId"})
     * @Rest\QueryParam(
     *      name="id",
     *      requirements="\d+",
     *      nullable=false,
     *      description="Case Id"
     * )
     * @Rest\QueryParam(
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
            return $this->handleView($this->view(['message' => 'Invalid channel type.'], Codes::HTTP_BAD_REQUEST));
        }

        $this->get('orocrm_zendesk.model.sync_manager')->syncCase($caseEntity, $channel, true);

        return $this->handleView($this->view('', Codes::HTTP_OK));
    }
}
