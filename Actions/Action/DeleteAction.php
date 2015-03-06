<?php
/**
 * This file is part of the vardius/crud-bundle package.
 *
 * (c) Rafał Lorenz <vardius@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vardius\Bundle\CrudBundle\Actions\Action;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Vardius\Bundle\CrudBundle\Actions\Action;
use Vardius\Bundle\CrudBundle\Event\ActionEvent;
use Vardius\Bundle\CrudBundle\Event\CrudEvent;
use Vardius\Bundle\CrudBundle\Event\CrudEvents;

/**
 * DeleteAction
 *
 * @author Rafał Lorenz <vardius@gmail.com>
 */
class DeleteAction extends Action
{
    /** @var FormFactory */
    protected $formFactory;
    /** @var  EntityManager */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     * @param FormFactory $formFactory
     * @param TwigEngine $templating
     */
    function __construct(EntityManager $entityManager, FormFactory $formFactory, TwigEngine $templating)
    {
        parent::__construct($templating);
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function call(ActionEvent $event)
    {
        $request = $event->getRequest();
        $dataProvider = $event->getDataProvider();
        $controller = $event->getController();
        $id = $request->get('id');

        $data = $dataProvider->get($id);

        if ($data === null) {
            throw new EntityNotFoundException('Not found error');
        }

        $crudEvent = new CrudEvent($dataProvider->getSource(), $controller, $data);
        $this->dispatcher->dispatch(CrudEvents::CRUD_PRE_DELETE, $crudEvent);

        try {
            $dataProvider->remove($data->getId());
        } catch (\Exception $e) {
            $message = null;
            if (is_object($data) && method_exists($data, '__toString')) {
                $message = 'Error while deleting "' . $data . '"';
            } else {
                $message = 'Error while deleting element with id "' . $id . '"';
            }

            $this->getFlashBag($request)->add('error', $message);
        }

        $this->dispatcher->dispatch(CrudEvents::CRUD_POST_DELETE, $crudEvent);

        return $controller->redirect($this->getRefererUrl($controller, $request));
    }

    /**
     * {@inheritdoc}
     */
    protected function getFlashBag(Request $request)
    {
        return $request->getSession()->getFlashBag();
    }

    /**
     * {@inheritdoc}
     */
    public function getEventsNames()
    {
        return [
            'delete',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateName()
    {
        return 'delete';
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteDefinition()
    {
        return array(
            'pattern' => '/delete/{id}',
            'requirements' => array(
                'id' => '\d+'
            )
        );
    }

}
