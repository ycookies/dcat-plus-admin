<?php

namespace Dcatplus\Admin\Form\Concerns;

use Dcatplus\Admin\Form\Field;

trait HandleCascadeFields
{
    /**
     * @param  array  $dependency
     * @param  \Closure  $closure
     * @return Field\CascadeGroup
     */
    public function cascadeGroup(\Closure $closure, array $dependency)
    {
        $this->pushField($group = new Field\CascadeGroup($dependency));

        call_user_func($closure, $this);

        $this->html($group->end())->plain();

        return $group;
    }
}
