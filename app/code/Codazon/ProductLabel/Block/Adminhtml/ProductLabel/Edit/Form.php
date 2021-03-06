<?php
namespace Codazon\ProductLabel\Block\Adminhtml\ProductLabel\Edit;

use Magento\Backend\Block\Widget\Form as WidgetForm;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
	/**
     * @var \Magento\Store\Model\System\Store
     */
	protected $_systemStore;

	 /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('productlabel_form');
        $this->setTitle(__('Label Information'));
    }
	/**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
				'data' => [
					'id' => 'edit_form',
					'action' => $this->getData('action'),
					'method' => 'post',
					'enctype' => 'multipart/form-data'
				]
			]
        );

        
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
	protected function _prepareLayout()
    {
        \Magento\Framework\Data\Form::setElementRenderer(
            $this->getLayout()->createBlock(
                'Magento\Backend\Block\Widget\Form\Renderer\Element',
                $this->getNameInLayout() . '_element'
            )
        );
        \Magento\Framework\Data\Form::setFieldsetRenderer(
            $this->getLayout()->createBlock(
                'Magento\Backend\Block\Widget\Form\Renderer\Fieldset',
                $this->getNameInLayout() . '_fieldset'
            )
        );
        \Magento\Framework\Data\Form::setFieldsetElementRenderer(
            $this->getLayout()->createBlock(
                'Codazon\ProductLabel\Block\Adminhtml\ProductLabel\AbstractHtmlField\Element',
                $this->getNameInLayout() . '_fieldset_element'
            )
        );
    }
	
}