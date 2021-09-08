<?php
/**
 * Copyright © Landofcoder LLC. All rights reserved.
 * See COPYING.txt for license details.
 * http://landofcoder.com | info@landofcoder.com
 */

namespace Ves\All\Block\Adminhtml;

use Exception;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Validator\EmailAddress;
use Ves\All\Helper\Data;
use Zend_Mail;
use Zend_Mail_Exception;
use Zend_Mail_Transport_Smtp;
use Zend_Validate;
use Zend_Validate_Exception;

/**
 * Class Verify
 * @package Ves\All\Block\Adminhtml
 */
class Verify extends Template
{
    /**
     * @var Data
     */
    protected $_dataHelper;

    /**
     * @var string
     */
    protected $storeId;

    /**
     * @var string
     */
    protected $hash;

    /**
     * Remove values from global post and store values locally
     * @var array()
     */
    protected $configFields = [
        'active' => '',
        'name' => '',
        'auth' => '',
        'ssl' => '',
        'smtphost' => '',
        'smtpport' => '',
        'username' => '',
        'password' => '',
        'set_reply_to' => '',
        'set_from' => '',
        'set_return_path' => '',
        'return_path_email' => '',
        'custom_from_email' => '',
        'email' => '',
        'from_email' => ''
    ];

    /**
     * EmailTest constructor.
     * @param Context $context
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_dataHelper = $dataHelper;
        $this->init();
    }

    /**
     * @param $id
     * @return $this
     */
    public function setStoreId($id)
    {
        $this->storeId = $id;
        return $this;
    }

    /**
     * @return int \ null
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param null $key
     * @return array|mixed|string
     */
    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->configFields;
        } elseif (!array_key_exists($key, $this->configFields)) {
            return '';
        } else {
            return $this->configFields[$key];
        }
    }

    /**
     * @param null $key
     * @param string $value
     * @return array|mixed|string
     */
    public function setConfig($key, $value = null)
    {
        if (array_key_exists($key, $this->configFields)) {
            $this->configFields[$key] = $value;
        }

        return $this;
    }

    /**
     * Load default config if config is lock using "bin/magento config:set"
     */
    public function loadDefaultConfig()
    {
        $request = $this->getRequest();
        $formPostArray = (array) $request->getPost();

        $fields = array_keys($this->configFields);
        foreach ($fields as $field) {
            if (!array_key_exists($field, $formPostArray)) {
                $this->setConfig($field, $this->_dataHelper->getConfig($field), $this->getStoreId());
            } else {
                $this->setConfig($field, $request->getPost($field));
            }
        }

        return $this;
    }

    /**
     * @return void
     */
    protected function init()
    {
        $request = $this->getRequest();
        $this->setStoreId($request->getParam('store', null));

        $this->loadDefaultConfig();

        $this->hash = time() . '.' . rand(300000, 900000);
    }

    /**
     * @return array
     */
    public function verify()
    {
        $settings = [
            'server_license' => 'validateServerLicenseSetting'
        ];

        $result = $this->error();
        $hasError = false;

        foreach ($settings as $functionName) {
            $result = call_user_func([$this, $functionName]);

            if (array_key_exists('has_error', $result)) {
                if ($result['has_error'] === true) {
                    $hasError = true;
                    break;
                }
            } else {
                $hasError = true;
                $result = $this->error(true, 'License - Unknown Error');
                break;
            }
        }

        if (!$hasError) {
            $result['msg'] = __('and flush your Magento cache');
        }

        return [$result];
    }

    public function validateServerLicenseSetting() 
    {
        $result = $this->error();
        return  $result;
    }

   
    /**
     * Format error msg
     * @param string $s
     * @return string
     */
    public function formatErrorMsg($s)
    {
        return preg_replace(
            '@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@',
            '<a href="$1" target="_blank">$1</a>',
            nl2br($s)
        );
    }

    /**
     * @param bool $hasError
     * @param string $msg
     * @return array
     */
    public function error($hasError = false, $msg = '')
    {
        return [
            'has_error' => (bool) $hasError,
            'msg' => (string) $msg
        ];
    }
}
