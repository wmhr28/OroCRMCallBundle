<?php

namespace OroCRM\Bundle\CallBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

use OroCRM\Bundle\CallBundle\Entity\Call;
use OroCRM\Bundle\CallBundle\Entity\Manager\CallActivityManager;

class CallHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var CallActivityManager
     */
    protected $callActivityManager;

    /**
     * @var EntityRoutingHelper
     */
    protected $entityRoutingHelper;

    /**
     * @param FormInterface $form
     * @param Request       $request
     * @param ObjectManager $manager
     * @param CallActivityManager $callActivityManager
     * @param EntityRoutingHelper $entityRoutingHelper
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        CallActivityManager $callActivityManager,
        EntityRoutingHelper $entityRoutingHelper
    ) {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->callActivityManager = $callActivityManager;
        $this->entityRoutingHelper = $entityRoutingHelper;
    }

    /**
     * Process form
     *
     * @param  Call $entity
     * @return bool  True on successful processing, false otherwise
     */
    public function process(Call $entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {

                $target = $this->getTargetEntity();

                if ($target) {
                    $this->callActivityManager->addAssociation($entity, $target);
                }

                $this->onSuccess($entity);
                return true;
            }
        }

        return false;
    }

    /**
     * Get object of activity owner
     *
     * @return object|null
     */
    protected function getTargetEntity()
    {
        /** @var string $entityClass */
        $entityClass = $this->entityRoutingHelper->decodeClassName(
            $this->request->get('entityClass')
        );
        /** @var integer $entityId */
        $entityId = $this->request->get('entityId');

        if ($entityClass && $entityId) {
            $entity = $this->manager->getRepository($entityClass)->find($entityId);
        } else {
            $entity = null;
        }

        return $entity;
    }

    /**
     * "Success" form handler
     *
     * @param Call $entity
     */
    protected function onSuccess(Call $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
