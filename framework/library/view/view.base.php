<?php

/**
 * 视图基本类
 *
 * 先尽量简化，实现模板功能即可
 *
 * @package view
 */
class viewBase
{

    private $_params = array();

    //>>author snow 删除参数.以便可以传入数组.
    public function setVar()
    {
        $args = func_get_args();
        switch (count($args)) {
            case 1:
                if (!is_array($args[0])) {
                    throw new exception2('Invalid Template->assign()');
                }
                foreach ($args[0] as $name => $val) {
                    $this->_params[$name] = $val;
                }
                break;
            case 2:
                $this->_params[$args[0]] = $args[1];
                break;
            default:
                throw new exception2('Invalid Template->assign()');
                break;
        }

        return $this;
    }

    /**
     * Returns the value identified by the key $key
     *
     * @param name The key
     * @return The value associated to that key
     */
    public function getVar($name)
    {
        return $this->_params[$name];
    }

    public function getAllVars()
    {
        return $this->_params;
    }

    /**
     * Renders the view. Here we would ideally call a template engine, using the
     * values in $this->_params to fill the template 'context' and then display
     * everything.
     */
    function render()
    {
        //throw a exception
        throw new exception2('This method must be covered!');
    }

}
?>