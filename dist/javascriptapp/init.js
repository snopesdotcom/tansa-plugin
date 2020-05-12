window.tansa = {};
window.tansa.settings = {};

window.initTansaObject = function(){
	window.isLingofyMode = undefined;
	tansa.canShowLingofyTheme = undefined;

    if(!tansa.settings)
        tansa.settings = {};

	tansa.settings.baseUrl = createTansaClientBaseURL(tansaExtensionInfo.tansaServerURL);
    tansa.settings.userId =  tansaExtensionInfo.wpUserId;
    tansa.settings.clientExtenstionJs = 'tansa4ClientExtensionSimple.js';
    tansa.settings.theme = 'tansa-default';
    tansa.settings.parentAppId = '55a8be37-d788-4e2e-8116-66c557dbc7b8';
    tansa.settings.parentAppVersion = tansaExtensionInfo.wpVersion;
    tansa.settings.extensionName = 'tansa-wordpress';
	tansa.settings.extensionVersion = tansaExtensionInfo.version;
	tansa.settings.langCode = tansaExtensionInfo.parentAppLangCode;
	tansa.settings.connectionMenuRequired = false;
}

function createTansaClientBaseURL(tansaServerURL) {
	tansaServerURL = tansaServerURL.toLowerCase().trim();
	while (tansaServerURL.endsWith("/"))
        tansaServerURL = tansaServerURL.substring(0, tansaServerURL.length - 1);

    return tansaServerURL + "/tansaclient/";
}