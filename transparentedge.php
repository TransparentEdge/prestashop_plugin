<?php
declare(strict_types=1);

use Transparent\TransparentEdge\PurgeCache;

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

class Transparentedge extends Module
{
	/**
     * Flash bag name for invalidate urls
     */
    const FLASHBAG_NAME = 'transparentInvalidateUrls';

	const CONFIG_COMPANY_ID = 'TRANSPARENT_CDN_COMPANY_ID';

	const CONFIG_CLIENT_KEY = 'TRANSPARENT_CDN_CLIENT_KEY';

	const CONFIG_SECRET_KEY = 'TRANSPARENT_CDN_SECRET_KEY';

	const MODULE_ADMIN_NAME = 'Modules.Transparentedge.Admin';

	protected $html = '';

    protected $company_id;

    protected $client_key;

    protected $secret_key;

	public function __construct()
    {
		$this->name = 'transparentedge';
        $this->tab = 'administration';
        $this->version = '1.0.1';
        $this->author = 'Desarrollo Transparent';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

		if ($this->id) {
            $this->init();
        }

        $this->displayName = $this->trans('Transparent CDN', [], self::MODULE_ADMIN_NAME);
        $this->description = $this->trans('Invalidate Transparent CDN cache.', [], self::MODULE_ADMIN_NAME);

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], self::MODULE_ADMIN_NAME);

        if (!Configuration::get('TRANSPARENTEDGE')) {
            $this->warning = $this->l('No name provided');
        }
	}

	protected function init()
    {
		$this->company_id = (string) Configuration::get(self::CONFIG_COMPANY_ID);
        $this->client_key = (string) Configuration::get(self::CONFIG_CLIENT_KEY);
        $this->secret_key = (string) Configuration::get(self::CONFIG_SECRET_KEY);
	}

	public function install()
	{
		 if (
			!parent::install() ||
            !$this->registerHook('actionClearCache') ||
            !$this->registerHook('actionClearCompileCache') ||
            !$this->registerHook('actionClearSf2Cache') ||
			!$this->registerHook('actionCategoryUpdate') ||
			!$this->registerHook('actionObjectUpdateAfter') ||
			!$this->registerHook('actionDispatcherAfter') ||
			!$this->registerHook('actionObjectProductUpdateAfter') ||
			!$this->registerHook('actionAdminLoginControllerLoginAfter')
			){
            return false;
        }
		return true;
	}

	public function uninstall()
	{
		return parent::uninstall();

		// OLIVERCG: Added deletion of global variables if the module is uninstalled
        if (Configuration::get(self::CONFIG_COMPANY_ID)) {
            Configuration::deleteByName(self::CONFIG_COMPANY_ID);
        }
        if (Configuration::get(self::CONFIG_CLIENT_KEY)) {
            Configuration::deleteByName(self::CONFIG_CLIENT_KEY);
        }
        if (Configuration::get(self::CONFIG_SECRET_KEY)) {
            Configuration::deleteByName(self::CONFIG_SECRET_KEY);
        }
	}

	public function getContent()
    {
        $this->context->controller->addJqueryUi('ui.widget');
        $this->context->controller->addJqueryPlugin('tagify');

        $this->html = '';

        $this->postProcess();

        $this->html .= $this->renderForm();

        return $this->html;
    }

    protected function postProcess()
    {
		$errors = [];
		if(Tools::isSubmit('submitTransparentConfig')){
			if(!Tools::getValue(self::CONFIG_COMPANY_ID)){
				$errors[] = $this->trans('Company ID is required.', [], self::MODULE_ADMIN_NAME);
			} else {
				if (!Configuration::updateValue(self::CONFIG_COMPANY_ID, (string) Tools::getValue(self::CONFIG_COMPANY_ID))) {
                    $errors[] = $this->trans('Cannot update Company ID.', [], self::MODULE_ADMIN_NAME);
                }
			}

			if(!Tools::getValue(self::CONFIG_CLIENT_KEY)){
				$errors[] = $this->trans('Client Key is required.', [], self::MODULE_ADMIN_NAME);
			} else {
				if (!Configuration::updateValue(self::CONFIG_CLIENT_KEY, (string) Tools::getValue(self::CONFIG_CLIENT_KEY))) {
                    $errors[] = $this->trans('Cannot update Client Key.', [], self::MODULE_ADMIN_NAME);
                }
			}

			if(!Tools::getValue(self::CONFIG_SECRET_KEY)){
				$errors[] = $this->trans('Secret Key is required.', [], self::MODULE_ADMIN_NAME);
			} else {
				if (!Configuration::updateValue(self::CONFIG_SECRET_KEY, (string) Tools::getValue(self::CONFIG_SECRET_KEY))) {
                    $errors[] = $this->trans('Cannot update Secret Key.', [], self::MODULE_ADMIN_NAME);
                }
			}
		}

		if (count($errors) > 0) {
            $this->html .= $this->displayError(implode('<br />', $errors));
        } elseif (Tools::isSubmit('submitTransparentConfig')) {
            $this->html .= $this->displayConfirmation($this->trans('Settings updated successfully', [], self::MODULE_ADMIN_NAME));
        }

		$this->init();
	}

	 public function renderForm()
    {
		 $inputs[] = [
			'type' => 'text',
			'label' => $this->trans('Company ID', [], self::MODULE_ADMIN_NAME),
			'name' => self::CONFIG_COMPANY_ID,
			'class' => 'fixed-width-xxl',
			'desc' => $this->trans('Company ID.', [], self::MODULE_ADMIN_NAME),
			'required' => true
		];
		 $inputs[] = [
			'type' => 'text',
			'label' => $this->trans('Client Key', [], self::MODULE_ADMIN_NAME),
			'name' => self::CONFIG_CLIENT_KEY,
			'desc' => $this->trans('Client Key.', [], self::MODULE_ADMIN_NAME),
			'required' => true
		];
		 $inputs[] = [
			'type' => 'text',
			'label' => $this->trans('Secret Key', [], self::MODULE_ADMIN_NAME),
			'name' => self::CONFIG_SECRET_KEY,
			'desc' => $this->trans('Secret Key.', [], self::MODULE_ADMIN_NAME),
			'required' => true
		];
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Transparent CDN', [], self::MODULE_ADMIN_NAME),
                    'icon' => 'icon-cogs',
                ],
                'input' => $inputs,
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitTransparentConfig',
                ],
            ],
        ];

		$helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitTransparentConfiguration';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

		return $helper->generateForm([$fields_form]);

	}

	public function getConfigFieldsValues()
    {
        return [
            self::CONFIG_COMPANY_ID => Tools::getValue(self::CONFIG_COMPANY_ID, Configuration::get(self::CONFIG_COMPANY_ID)),
            self::CONFIG_CLIENT_KEY => Tools::getValue(self::CONFIG_CLIENT_KEY, Configuration::get(self::CONFIG_CLIENT_KEY)),
            self::CONFIG_SECRET_KEY => Tools::getValue(self::CONFIG_SECRET_KEY, Configuration::get(self::CONFIG_SECRET_KEY))
        ];
    }

	public function hookActionClearCache($params){
		//$this->getTransparentPurgeCache()->sendPurgeRequest();
	}

	public function hookActionClearCompileCache($params){ 
		$this->getTransparentPurgeCache()->sendPurgeRequest();

	}

	public function hookActionClearSf2Cache($params){ 
		//$this->getTransparentPurgeCache()->sendPurgeRequest();
	}

	public function hookActionCategoryUpdate($params){
		$link = new Link();
		$url = $link->getCategoryLink($params['category']);
		$this->getTransparentPurgeCache()->sendPurgeRequest([$url]);
	}

	public function hookActionObjectUpdateAfter($params){
		$controller = Tools::getValue('controller');
		$action = Tools::getValue('action');
		//cms pages
		if(get_class($params['object']) == "CMS" && $controller == "AdminCmsContent" && $action == "updatecms"){
			$link = new Link();
			$url = $link->getCMSLink($params['object']);
			$this->getTransparentPurgeCache()->sendPurgeRequest([$url]);
		}
	}

	public function hookActionObjectProductUpdateAfter($params){
		$urls = [];
		if($this->getContainer()->get('session')->getFlashBag()->has(self::FLASHBAG_NAME)){
			$urls = $this->getContainer()->get('session')->getFlashBag()->get(self::FLASHBAG_NAME);
		}
		$link = new Link();
		$urls[] = $link->getProductLink($params['object']);
		//get categories
		foreach($params['object']->getParentCategories() as $category){
			$urls[] = $link->getCategoryLink($category['id_category']);
		}
		//get manufacturer
		$urls[] = $link->getManufacturerLink($params['object']->id_manufacturer);			
		$this->getContainer()->get('session')->getFlashBag()->set(self::FLASHBAG_NAME, array_unique($urls));
	}

	/**
	 * This hook executes code after login process in the case it's successful
	 * @param  Array $params Params like 'controller' => $this, 'employee' => $this->context->employee, 'redirect' => $url
	 * @author Oliver CG
	 */
    public function hookActionAdminLoginControllerLoginAfter($params)
    {
    	// OLIVERCG: Set a cookie to 1 when logged for 1 hour
        $cookieName = 'adm-tcdn';
        $cookieValue = '1';
        $cookieTime = time() + 3600;

        setcookie($cookieName, $cookieValue, $cookieTime, '/');
    }

	public function hookActionDispatcherAfter($params){
		$controller = Tools::getValue('controller');
		$action = Tools::getValue('action');
		if($controller == "AdminProducts" && $action == "updateproduct"){
			if($this->getContainer()->get('session')->getFlashBag()->has(self::FLASHBAG_NAME)){
				$this->getTransparentPurgeCache()->sendPurgeRequest($this->getContainer()->get('session')->getFlashBag()->get(self::FLASHBAG_NAME));
			}
		}
	}
	


	private function getTransparentPurgeCache(): PurgeCache
    {
        /** @var PurgeCache $purgeCache */
        $purgeCache = $this->get('transparent.transparentedge.common.purge_cache');
        return $purgeCache;
    }

}