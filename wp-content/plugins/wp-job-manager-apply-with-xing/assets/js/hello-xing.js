//
// XING
//
(function(hello){

function formaterror(o){
	if (o.error){
		var message = o.error;
		o.error = {
			code : "request_failed",
			message : message
		};
	}
}

var base = "https://api.xing.com";

hello.init({
	'xing' : {

		oauth : {
			version : "1",
			auth	: base + "/v1/authorize",
			request : base + "/v1/request_token",
			token	: base + "/v1/access_token"
		},

		base	: base + "/v1/",

		get : {
			me : 'users/me'
		},

		wrap : {
			me : function(res){
				formaterror(res);
				return res;
			},
			"default" : function(res){
				formaterror(res);
				return res;
			}
		},
		jsonp : false,
		xhr : function( p, qs ) {
			//console.log( p );
			//console.log( qs );
			return true;
		}
	}
});

})(hello);