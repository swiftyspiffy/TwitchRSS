var completedCaptcha;
var correctCaptcha = function(response) {
        completedCaptcha = response;
};

$(document).ready(function(){ 
	$('#add_stream').click(function() {
		var channel = $('#twitch_channel').val();
		var feed = $('input[name="feed_type"]:checked').val();
		addFeed(channel, feed);
	});
});

function addFeed(channel, feed) {
	$.post("api.php?action=add", { channel: channel, feed: feed, token: completedCaptcha }, function(result){
		if(result.success) {
			window.location.href = result.message;
		} else {
			alert("Failed! Error: " + result.message);
			location.reload();
		}
	}, "json");
}