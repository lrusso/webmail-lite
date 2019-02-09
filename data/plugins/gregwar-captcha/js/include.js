(function () {
	
	function ShowOnStart(oViewModel)
	{
		var
			oSettings = AfterLogicApi.getPluginSettings('GregwarCaptcha'),
			bShowOnStart = true
		;

		if ('CLoginViewModel' === oViewModel.__name)
		{
			bShowOnStart = oSettings ? oSettings.ShowOnStart : false;
		}

		if (bShowOnStart)
		{
			oViewModel.showGregwarCaptcha();
		}
	};
	
	AfterLogicApi.addPluginHook('view-model-defined', function (sViewModelName, oViewModel) {
		if (oViewModel && ('CLoginViewModel' === sViewModelName || 
				'CRegisterViewModel' === sViewModelName || 'CForgotViewModel' === sViewModelName))
		{
			var sType = 'Login';
			if ('CRegisterViewModel' === sViewModelName)
			{
				sType = 'Register';
			}
			else if ('CForgotViewModel' === sViewModelName)
			{
				sType = 'Forgot';
			}
			
			oViewModel.gregwarCaptcha = ko.observable('');
			oViewModel.gregwarShow = ko.observable(false);
			oViewModel.gregwarSrcHash = ko.observable(Math.random().toString().substring(3));
			oViewModel.gregwarSrc = ko.computed(function () {
				return '?/gregwar-captcha/' + sType + '/' + oViewModel.gregwarSrcHash() + '/';
			});
			oViewModel.reloadGregwarCaptcha = function () {
				oViewModel.gregwarSrcHash(Math.random().toString().substring(3));
			};
			oViewModel.showGregwarCaptcha = function () {
				oViewModel.gregwarShow(true);
			};
			
			AfterLogicApi.addPluginHook('ajax-default-request', function (sAction, oParameters) {
				
				var
					bLogin = ('CLoginViewModel' === sViewModelName && 'SystemLogin' === sAction),
					bRegister = ('CRegisterViewModel' === sViewModelName && 'RegisterAccount' === sAction),
					bForgot = ('CForgotViewModel' === sViewModelName && 'GetForgotAccountQuestion' === sAction)
				;
				
				if ((bLogin || bRegister || bForgot) && oParameters && oViewModel.gregwarShow())
				{
					oParameters['CustomRequestData'] = oParameters['CustomRequestData'] || {};
					oParameters['CustomRequestData']['GregwarCaptchaData'] = oViewModel.gregwarCaptcha();
				}
			});

			AfterLogicApi.addPluginHook('ajax-default-response', function (sAction, oData) {
				if (
					('CLoginViewModel' === sViewModelName && 'SystemLogin' === sAction) ||
					('CRegisterViewModel' === sViewModelName && 'RegisterAccount' === sAction) ||
					('CForgotViewModel' === sViewModelName && 'GetForgotAccountQuestion' === sAction)
				)
				{
					if (!oData || !oData['Result'])
					{
						if (oViewModel.gregwarShow())
						{
							oViewModel.reloadGregwarCaptcha();
						}
						else if (oData && oData['Captcha'])
						{
							oViewModel.showGregwarCaptcha();
						}
					}
				}
			});
			
			ShowOnStart(oViewModel);
		}
	});
	
}());