<?php

namespace OroCRM\Bundle\ZendeskBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @NamePrefix("oro_api_")
 */
class TicketController extends FOSRestController implements ClassResourceInterface
{
    /**
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
     *      description="Sync case with zendesk",
     *      resource=true
     * )
     * @AclAncestor("orocrm_case_update")
     *
     * @return Response
     */
    public function postSyncCaseAction()
    {
        $syncManager = $this->get('orocrm_zendesk.model.sync_manager');
        $oroEntityProvider = $this->get('orocrm_zendesk.entity_provider.oro');
        $id = (int)$this->getRequest()->get('id');
        $channelId = (int)$this->getRequest()->get('channelId');
        $channel = $oroEntityProvider->getChannelById($channelId);
        $caseEntity = $oroEntityProvider->getCaseById($id);

        if (!$channel) {
            return $this->handleView($this->view('Channel not found', Codes::HTTP_NOT_FOUND));
        }
        if (!$caseEntity) {
            return $this->handleView($this->view('Case Entity not found', Codes::HTTP_NOT_FOUND));
        }

        $syncManager->syncCase($caseEntity, $channel);
        return $this->handleView($this->view('', Codes::HTTP_OK));
    }
}
