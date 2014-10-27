//Polar clock is modified from Lennart Hase's HTML 5 Canvas Polar Clock - http://codepen.io/motorlatitude/details/uevDx
var usr_color = 430; //Change value to change color scheme
var startDate;
var endDate;
var now;

window.requestAnimFrame = (function(){
    return  window.requestAnimationFrame ||
    window.webkitRequestAnimationFrame ||
    window.mozRequestAnimationFrame    ||
    window.oRequestAnimationFrame      ||
    window.msRequestAnimationFrame     ||
    function( callback ){
        window.setTimeout(callback, 1000 / 60);
    };
})();

var canvas;
var ctx;
var w = 600, h = 600;
var arcs;

function init(){
    reset();
    arcs = [];
    var h = new arc();
    h.class = "hours";
    h.r = 168;
    arcs.push(h);

    var m = new arc();
    m.class = "mins";
    m.r = 134;
    arcs.push(m);

    var s = new arc();
    s.class = "secs";
    s.r = 100;
    arcs.push(s);
}

function arc(){
    this.draw = function(){
        ctx.beginPath();
        ctx.arc(300,300,this.r,(Math.PI/(2/3)),this.rot,false);
        ctx.lineWidth = 35;
        if(now.getTime() < startDate.getTime()){
            ctx.strokeStyle = "hsla(113, 100%, 37%, 0.69)";
        }else if(now.getTime() > endDate.getTime()){
            ctx.strokeStyle = "hsla(360, 100%, 37%, 0.69)";
        }else{
            ctx.strokeStyle = "hsla("+(this.rot*(180/Math.PI)+usr_color)+",1000%,50%,1)";
        }
        ctx.stroke();

        ctx.save();
        ctx.fillStyle = "#333";
        ctx.translate(300, 300);
        ctx.rotate(this.rot);
        ctx.font="14px Arial Rounded MT Bold";
        ctx.restore();
    }
}

function reset(){
    ctx.fillStyle = "#333";
    ctx.fillRect(0,0,w,h);
}

function draw(){
    reset();
    ctx.fillStyle = "rgba(255,255,255,0.5)";
    for(var i=0;i<arcs.length;i++){
        var a = arcs[i];

        now = new Date();
        var difHours = endDate.getHours()-now.getHours()-1;
        var difMins = endDate.getMinutes()-now.getMinutes()-1;
        var difSecs = endDate.getSeconds()-now.getSeconds();
        var difMillis = endDate.getMilliseconds()-now.getMilliseconds();

        //console.log(difHours+"  "+difMins+"  "+difSecs+"  "+difMillis);

        if(a.class == "hours"){
            a.rot = ((difHours/12)*(Math.PI*2) - (Math.PI/2));// + ((difMins/3600)*(Math.PI*2));
            if(difHours == 0)
                a.rot = -1.5701;
        }
        else if(a.class == "mins"){
            a.rot = ((difMins/60)*(Math.PI*2) - (Math.PI/2));// + ((difSecs/3600)*(Math.PI*2));
            if(difMins == 0)
                a.rot = -1.5701;
        }
        else if(a.class == "secs"){
            a.rot = ((difSecs/60)*(Math.PI*2) - (Math.PI/2)) + ((difMillis/60000)*(Math.PI*2));
            if(difSecs == 0 && difMins == 0)
                a.rot = -1.5701;
        }

        //Game is no longer running so lock the timer
        if(now.getTime() < startDate.getTime() || now.getTime() > endDate.getTime()){
            a.rot = ((Math.PI*2) - (Math.PI/2)) + ((Math.PI*2));
        }
        a.draw();
    }
    var seconds;
    var minutes;
    var hours;
    var timeDif;

    ctx.font = "15px PressStart";
    ctx.textAlign = 'center';



    //Draw a text based countdown timer for the less adventurous players
    if(startDate.getTime() > now.getTime()){
        timeDif = startDate.getTime() - now.getTime();
        hours = Math.floor(timeDif/3600000);
        timeDif %= 3600000;
        minutes = Math.floor(timeDif/60000);
        timeDif %= 60000;
        seconds = Math.floor(timeDif/1000);
        ctx.fillText((hours < 10 ? "0"+hours:hours) + ":" + (minutes < 10 ? "0"+minutes:minutes) + ":" + (seconds < 10 ? "0"+seconds:seconds), w / 2, h / 2);

    }else if(endDate.getTime() > now.getTime()){

        timeDif = endDate.getTime() - now.getTime();
        hours = Math.floor(timeDif/3600000);
        timeDif %= 3600000;
        minutes = Math.floor(timeDif/60000);
        timeDif %= 60000;
        seconds = Math.floor(timeDif/1000);
        ctx.fillText((hours < 10 ? "0"+hours:hours) + ":" + (minutes < 10 ? "0"+minutes:minutes) + ":" + (seconds < 10 ? "0"+seconds:seconds), w / 2, h / 2);
    } else {
        ctx.fillText("GAME OVER", w / 2, h / 2);
    }

}

function animloop() {
    draw();
    requestAnimFrame(animloop);
}

$( document ).ready(function() {
    if(window.location.pathname.indexOf("index.php") == -1 && window.location.pathname != "/"){//Only draw the clock on the index page
        return;
    }
    canvas = document.getElementsByTagName("canvas")[0];

    startDate = new Date($("#clock").attr('startdate'));
    endDate = new Date((startDate.getTime() + $("#clock").attr('duration') * 3600000));
    ctx = canvas.getContext("2d");
    canvas.width = w;
    canvas.height = h;

    arcs = [];

    init();
    animloop();
});
