/**
 * Dabros version 0.1.0
 * RPC Library for PHP & JavaScript
 *
 * @author  Dmitry Bystrov <uncle.demian@gmail.com>, 2013
 * @source  https://github.com/dab-libs/dabros
 * @date    2013-03-08
 * @license Lesser GPL licenses (http://www.gnu.org/copyleft/lesser.html)
 */

/**
 * JavaScript API для взаимодействия с системой удаленный объектов Dabros
 */
var dabros = {};

dabros.RemoteObjectFactory = (function($) {

	function RemoteObjectFactoryClass(serviceUrl)
	{
		var self = this;
		/**
		 * Приватные переменные и методы
		 */
		var privates = {
			requestQueue: [],
			lastRequestId: 0,
			requestTimer: null,
			notifiableObjectList: {},

			registerRequest: function(requestInfo, listeners, systemCallback)
			{
				requestInfo.id = privates.getNextId();
				var request = new Request(requestInfo, listeners, systemCallback);
				privates.requestQueue.push(request);
				clearTimeout(this.requestTimer);
				privates.requestTimer = setTimeout($.proxy(this.sendRequestQueue, this), 10);
				return request;
			},

			sendRequestQueue: function()
			{
				if (privates.requestQueue.length == 0)
					return;

				var requests = {};
				var requestJsonList = [];
				for (var i = 0;  i < privates.requestQueue.length;  i++)
				{
					requestJsonList.push(privates.requestQueue[i].getJson());
					requests[privates.requestQueue[i].getId()] = privates.requestQueue[i];
				}
				privates.requestQueue = [];
				var requestText = '[' + requestJsonList.join(',') + ']';
				var xhr = $.ajax({
					url: serviceUrl,
					type: 'POST',
					data: {
						request: requestText
					},
					dataType: 'json',
					success: $.proxy(this.onSuccess, this, requests),
					error:  $.proxy(this.onError, this, requests)
				});
			},

			onSuccess: function(requests, responses, textStatus, jqXHR)
			{
				for (var i = 0;  i < responses.length;  i++)
				{
					if (typeof(requests[responses[i].id]) != 'undefined')
					{
						var result = privates.createResult(responses[i].result);
						requests[responses[i].id].executeCallbacks(result);
						delete requests[responses[i].id];
					}
					else if (responses[i].id == -1 && typeof(responses[i].result) == 'object' && responses[i].result instanceof Array)
					{
						var notificationList = responses[i].result;
						for (var j = 0;  j < notificationList.length;  ++j)
						{
							var notification = notificationList[j];
							if (typeof(privates.notifiableObjectList[notification.objectId]) == 'object')
							{
								var notifiableObject = privates.notifiableObjectList[notification.objectId];
								executeCallbacksImpl(notifiableObject[notification.methodName], notification.result);
							}
						}
					}
				}
				privates.onError(requests);
			},

			onError: function(requests)
			{
			},

			createResult: function(responseResult)
			{
				var result = responseResult;
				if (typeof(responseResult) == 'object' && responseResult != null)
				{
					if (responseResult instanceof Array)
					{
						result = [];
						for (var i = 0;  i < responseResult.length;  i++)
						{
							if (typeof(responseResult[i]) != 'undefined')
								result[i] = privates.createResult(responseResult[i]);
						}
					}
					else if (typeof(responseResult.__ros__) != 'undefined')
					{
						result = privates.createProxy(responseResult);
					}
					else if (typeof(responseResult.__date__) == 'string')
					{
						result = new Date(responseResult.__date__);
					}
					else
					{
						result = {};
						for (var n in responseResult)
						{
							if (typeof(responseResult[n]) != 'undefined')
								result[n] = privates.createResult(responseResult[n]);
						}
					}
				}
				return result;
			},

			createProxy: function(objectInfo)
			{
				var proxy = new ProxyObject(objectInfo);
				return proxy;
			},

			getNextId: function()
			{
				return ++privates.lastRequestId;
			}
		}

		/**
		 */
		self.getSessionFacade = function(callback)
		{
			var requestInfo = {
				objectId: 0,
				methodName: 'getSessionFacade',
				params: [callback]
			};
			return privates.registerRequest(requestInfo);
		}

		function Request(requestInfo, listeners, systemCallback)
		{
			this.callbacks = [systemCallback];
			if (requestInfo.params.length > 0 && typeof(requestInfo.params[requestInfo.params.length - 1]) == 'function')
			{
				this.callbacks.push(requestInfo.params.pop());
			}
			if (typeof(listeners) == 'object' && listeners instanceof Array)
			{
				for (var i = 0;  i < listeners.length;  ++i)
					this.callbacks.push(listeners[i]);
			}
			var requestData = {
				objectId: requestInfo.objectId,
				method: requestInfo.methodName,
				params: requestInfo.params,
				id: requestInfo.id
			};
			if (typeof(requestInfo.indepedentClassName) == 'string')
				requestData.indepedentClassName = requestInfo.indepedentClassName;

			// Приватные переменные и методы
			var privates = {
				requestJson: $.toJSON(requestData),
				id: requestInfo.id
			};

			this.getJson = function()
			{
				return privates.requestJson;
			}

			this.getId = function()
			{
				return privates.id;
			}

			this.executeCallbacks = function(result)
			{
				executeCallbacksImpl(this.callbacks, result);
			}
		}

		function executeCallbacksImpl(callbacks, result)
		{
			if (typeof(callbacks) == 'object' && callbacks instanceof Array)
			{
				for (var i = 0;  i < callbacks.length;  ++i)
				{
					if (typeof(callbacks[i]) == 'function')
					{
						callbacks[i]({
							result: result
						});
					}
				}
			}
		}

		function ProxyObject(objectInfo)
		{
			var objectId = objectInfo.objectId;
			var methods = objectInfo.methods;
			var preloaded = (typeof(objectInfo.preloaded) == 'object' ? objectInfo.preloaded : {});
			var consts = (typeof(objectInfo.consts) == 'object' ? objectInfo.consts : {});
			var self = this;
			var listenerList = {};
			var lastResopnseList = {};
			self._addListener = function(methodName, callback)
			{
				if (typeof(listenerList[methodName]) == 'object' && typeof(listenerList[methodName].push) == 'function')
				{
					listenerList[methodName].push(callback);
					privates.notifiableObjectList[objectId] = listenerList;
				}
			}
			self._getLastResponse = function(methodName)
			{
				return {
					result: lastResopnseList[methodName]
				};
			}
			var registerRequest = function(methodName, params)
			{
				var requestInfo = {
					objectId: objectId,
					methodName: methodName,
					params: params
				};
				if (objectInfo.__ros__ == 'INDEPEDENT')
					requestInfo.indepedentClassName = objectInfo.indepedentClassName
				privates.registerRequest(requestInfo, listenerList[methodName], function(response)
				{
					lastResopnseList[methodName] = response;
				});
			}
			var createPreloadedMethod = function(methodName)
			{
				var preloadedTime = (new Date()).getTime();
				lastResopnseList[methodName] = privates.createResult(preloaded[methodName]);
				self[methodName] = function()
				{
					var nowTime = (new Date()).getTime();
					if (nowTime - preloadedTime < 1000)
					{
						var preloadedResult = lastResopnseList[methodName];
						var requestInfo = {
							id: 0,
							objectId: objectId,
							methodName: methodName,
							params: [].slice.call(arguments, 0)
						};
						var request = new Request(requestInfo, listenerList[methodName]);
						request.executeCallbacks(preloadedResult);
					}
					else
					{
						registerRequest(methodName, [].slice.call(arguments, 0));
					}
				}
			}
			var createConstMethod = function(methodName)
			{
				var constResult = lastResopnseList[methodName] = privates.createResult(consts[methodName]);
				self[methodName] = function()
				{
					var requestInfo = {
						id: 0,
						objectId: objectId,
						methodName: methodName,
						params: [].slice.call(arguments, 0)
					};
					var request = new Request(requestInfo, listenerList[methodName]);
					request.executeCallbacks(constResult);
				}
			}
			$.each(methods, function(k, methodName)
			{
				listenerList[methodName] = [];
				if (typeof(preloaded[methodName]) != 'undefined')
				{
					createPreloadedMethod(methodName);
				}
				else if (typeof(consts[methodName]) != 'undefined')
				{
					createConstMethod(methodName);
				}
				else
				{
					self[methodName] = function()
					{
						registerRequest(methodName, [].slice.call(arguments, 0));
					};
				}
			});
		}
	}

	return RemoteObjectFactoryClass;
})(jQuery);
