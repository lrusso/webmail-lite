<?php

/*
The MIT License (MIT)
Copyright (c) 2016, Afterlogic Corp.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

class_exists('CApi') or die();

class CGregwarCaptchaPlugin extends AApiPlugin
{
	/**
	 * @param CApiPluginManager $oPluginManager
	 */
	public function __construct(CApiPluginManager $oPluginManager)
	{
		parent::__construct('1.0', $oPluginManager);

		$this->AddHook('api-app-data', 'PluginApiAppData');
		$this->AddHook('webmail-login-custom-data', 'PluginWebmailLoginCustomData');
		$this->AddHook('webmail-register-custom-data', 'PluginWebmailRegisterCustomData');
		$this->AddHook('webmail-forgot-custom-data', 'PluginWebmailForgotCustomData');
		$this->AddHook('ajax.response-result', 'PluginAjaxResponseResult');
	}

	public function Init()
	{
		parent::Init();

		$this->SetI18N(true);
		$this->AddJsFile('js/include.js');

		$this->IncludeTemplate('Login_LoginViewModel', 'Login-Before-Submit-Button', 'templates/include/login.html');
		$this->IncludeTemplate('Login_RegisterViewModel', 'Register-Before-Submit-Button', 'templates/include/login.html');
		$this->IncludeTemplate('Login_ForgotViewModel', 'Forgot-Before-Submit-Button', 'templates/include/login.html');
		
		$this->AddServiceHook('gregwar-captcha', 'ServiceHook');
	}

	private function integrator()
	{
		static $oApiIntegrator = null;
		if (null === $oApiIntegrator)
		{
			$oApiIntegrator = CApi::Manager('integrator');
		}
		
		return $oApiIntegrator;
	}

	private function phraseKey($sType)
	{
		return 'Login/GregwarCaptcha/LastPhrase/'.$sType.'/'.$this->integrator()->GetCsrfToken();
	}

	private function limitKey()
	{
		return 'Login/GregwarCaptcha/Limit/'.$this->integrator()->GetCsrfToken();
	}
	
	public function ServiceHook($sName = '', $sType = '')
	{
		include('libs/Gregwar/Captcha/CaptchaBuilderInterface.php');
		include('libs/Gregwar/Captcha/PhraseBuilderInterface.php');
		include('libs/Gregwar/Captcha/CaptchaBuilder.php');
		include('libs/Gregwar/Captcha/PhraseBuilder.php');

		header('Content-type: image/jpeg');

		$oCaptcha = \Gregwar\Captcha\CaptchaBuilder::create();
		$oCaptcha->setBackgroundColor(255, 255, 255);
		$oCaptcha->build(200, 60);

		$oCacher = CApi::Cacher();
		if ($oCacher->IsInited())
		{
			$oCacher->Set($this->phraseKey($sType), $oCaptcha->getPhrase());
		}
		
		echo $oCaptcha->output();
	}

	/**
	 * @param bool $bAddToLimit = false
	 * @param bool $bClear = false
	 * @return int
	 */
	private function captchaLocalLimit($bAddToLimit = false, $bClear = false)
	{
		$iResult = 0;
		$oCacher = CApi::Cacher();
		if ($oCacher->IsInited())
		{
			if ($bClear)
			{
				$oCacher->Delete($this->limitKey());
			}
			else
			{
				$sData = $oCacher->Get($this->limitKey());
				if (0 < strlen($sData) && is_numeric($sData))
				{
					$iResult = (int) $sData;
				}

				if ($bAddToLimit)
				{
					$oCacher->Set($this->limitKey(), ++$iResult);
				}
			}
		}

		return $iResult;
	}

	public function PluginAjaxResponseResult($sAction, &$aResponseItem)
	{
		if (('SystemLogin' === $sAction || 'RegisterAccount' === $sAction || 'GetForgotAccountQuestion' === $sAction) && is_array($aResponseItem) && isset($aResponseItem['Result']))
		{
			if (!$aResponseItem['Result'] && isset($GLOBALS['P7_GREGWAR_CAPTCHA_ATTRIBUTE_ON_ERROR']) && $GLOBALS['P7_GREGWAR_CAPTCHA_ATTRIBUTE_ON_ERROR'])
			{
				$aResponseItem['Captcha'] = true;
			}

			if (isset($GLOBALS['P7_GREGWAR_CAPTCHA_LIMIT_CHANGE']) && $GLOBALS['P7_GREGWAR_CAPTCHA_LIMIT_CHANGE'])
			{
				if ($aResponseItem['Result'])
				{
					$this->captchaLocalLimit(false, true);
				}
				else
				{
					$this->captchaLocalLimit(true);
				}
			}
		}
	}

	public function PluginWebmailLoginCustomData($mCustomData)
	{
		$iLimitCaptcha = (int) CApi::GetConf('plugins.gregwar-captcha.options.limit-count', 0);
		if (0 < $iLimitCaptcha)
		{
			$GLOBALS['P7_GREGWAR_CAPTCHA_LIMIT_CHANGE'] = true;
			$iLimitCaptcha -= $this->captchaLocalLimit();
		}

		if (1 === $iLimitCaptcha)
		{
			$GLOBALS['P7_GREGWAR_CAPTCHA_ATTRIBUTE_ON_ERROR'] = true;
		}
		else if (0 >= $iLimitCaptcha)
		{
			if (empty($mCustomData['GregwarCaptchaData']))
			{
				$GLOBALS['P7_GREGWAR_CAPTCHA_ATTRIBUTE_ON_ERROR'] = true;
				throw new \ProjectCore\Exceptions\ClientException(\ProjectCore\Notifications::CaptchaError);
			}

			$sPhrase = '';
			$oCacher = CApi::Cacher();
			if ($oCacher->IsInited())
			{
				$sPhrase = $oCacher->Get($this->phraseKey('Login'), '');
				$oCacher->Delete($this->phraseKey('Login'));
			}

			if (empty($sPhrase) || $sPhrase !== $mCustomData['GregwarCaptchaData'])
			{
				$GLOBALS['P7_GREGWAR_CAPTCHA_ATTRIBUTE_ON_ERROR'] = true;
				throw new \ProjectCore\Exceptions\ClientException(\ProjectCore\Notifications::CaptchaError);
			}
		}
	}

	public function PluginWebmailRegisterCustomData($mCustomData)
	{
		if (empty($mCustomData['GregwarCaptchaData']))
		{
			$GLOBALS['P7_GREGWAR_CAPTCHA_ATTRIBUTE_ON_ERROR'] = true;
			throw new \ProjectCore\Exceptions\ClientException(\ProjectCore\Notifications::CaptchaError);
		}

		$sPhrase = '';
		$oCacher = CApi::Cacher();
		if ($oCacher->IsInited())
		{
			$sPhrase = $oCacher->Get($this->phraseKey('Register'), '');
			$oCacher->Delete($this->phraseKey('Register'));
		}

		if (empty($sPhrase) || $sPhrase !== $mCustomData['GregwarCaptchaData'])
		{
			$GLOBALS['P7_GREGWAR_CAPTCHA_ATTRIBUTE_ON_ERROR'] = true;
			throw new \ProjectCore\Exceptions\ClientException(\ProjectCore\Notifications::CaptchaError);
		}
	}
	
	public function PluginWebmailForgotCustomData($mCustomData)
	{
		if (empty($mCustomData['GregwarCaptchaData']))
		{
			$GLOBALS['P7_GREGWAR_CAPTCHA_ATTRIBUTE_ON_ERROR'] = true;
			throw new \ProjectCore\Exceptions\ClientException(\ProjectCore\Notifications::CaptchaError);
		}

		$sPhrase = '';
		$oCacher = CApi::Cacher();
		if ($oCacher->IsInited())
		{
			$sPhrase = $oCacher->Get($this->phraseKey('Forgot'), '');
			$oCacher->Delete($this->phraseKey('Forgot'));
		}

		if (empty($sPhrase) || $sPhrase !== $mCustomData['GregwarCaptchaData'])
		{
			$GLOBALS['P7_GREGWAR_CAPTCHA_ATTRIBUTE_ON_ERROR'] = true;
			throw new \ProjectCore\Exceptions\ClientException(\ProjectCore\Notifications::CaptchaError);
		}
	}

	public function PluginApiAppData($oDefaultAccount, &$aAppData)
	{
		if (isset($aAppData['Auth']) && !$aAppData['Auth'] &&
			isset($aAppData['Plugins']) && is_array($aAppData['Plugins']))
		{
			$aGregwarCaptcha = array(
				'ShowOnStart' => false
			);

			$iLimitCaptcha = (int) CApi::GetConf('plugins.gregwar-captcha.options.limit-count', 0);
			if (0 === $iLimitCaptcha)
			{
				$aGregwarCaptcha['ShowOnStart'] = true;
			}
			else
			{
				$iLimitCaptcha -= $this->captchaLocalLimit();
				if (0 >= $iLimitCaptcha)
				{
					$aGregwarCaptcha['ShowOnStart'] = true;
				}
			}

			$aAppData['Plugins']['GregwarCaptcha'] = $aGregwarCaptcha;
		}
	}
}

return new CGregwarCaptchaPlugin($this);
