<?php

namespace Geo\DataBundle\Form\Extension;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$param = array();
		foreach (array('longitude','latitude','height','title','label_longitude','label_latitude','label_title','label_height') as $v)
			if (isset($options['data'][$v]))
				$param[$v] = $options['data'][$v];
		// добавляем geopoint с параметрами $param
        $builder->add('geo', 'geopoint', $param);
    }
	
	
	public function getName()
    {
        return 'geoPoint';
    }
}

?>