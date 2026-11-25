let fs = require('fs'),
    app = require('express')(),
    options = {
        key: fs.readFileSync('/etc/letsencrypt/live/donuttrix.ru/privkey.pem'),
        cert: fs.readFileSync('/etc/letsencrypt/live/donuttrix.ru/fullchain.pem')
    }

const server = require('https').createServer(options, app);

let io = require('socket.io')(server, {

        cors: {
            origin: "https://donuttrix.ru",
            methods: ["GET", "POST"],
            allowedHeaders: ["X-Requested-With", "Content-Type"],
            credentials: true
        },
        serveClient: true,
        allowEIO3: true,
    }),
    redis = require('redis'),
    client = redis.createClient(),
    requestify = require('requestify'),
    crash           = require('./crash'),
    online = 0,
    ipsConnected = [];

server.listen(8443);

let spin = 0;
let last_color = '';

crash.init();

io.sockets.on('connection', function(socket) {
    var address = socket.handshake.address;
    if(!ipsConnected.hasOwnProperty(address)) {
        ipsConnected[address] = 1;
        online = online + 1;
    }
    updateOnline(online);
    socket.on('disconnect', function() {
        if(ipsConnected.hasOwnProperty(address)) {
            delete ipsConnected[address];
            online = online - 1;
        }
        updateOnline(online);
    });
});

function updateOnline(online) {
    io.sockets.emit('live', {'count': online});
}

client.subscribe("wheel");
client.subscribe("wheel_timer");
client.subscribe("withdraw");
client.subscribe("admin");
client.subscribe("test");
client.subscribe('jackpot.newBet');
client.subscribe('jackpot.timer');
client.subscribe('crash');

var wheel_timer_server = 15; // wheel timer
var timer_to_start = false; // timer until the game starts and the spin begins
var timer_of_animation;
var timer_to_restart = 0;
var game_started = false;
var game_id = 0;
var delayy = 0;

client.on('message', function(channel, message) {
    if(channel == 'wheel_timer') {
        startWheel();
    }
    if(channel == 'wheel') {
        let emit_type = JSON.parse(message);
        io.sockets.emit(emit_type.type,  JSON.parse(message));
    }
    if(channel == 'withdraw') {
        io.sockets.emit('withdraw',  JSON.parse(message));
    }
    if(channel == 'admin') {
        let e = JSON.parse(message);
        if(e.type == 'reloadWheel') reloadWheel();
        if(e.type == 'stopWheel') stopWheel();
    }

    if(channel == 'test') {
        io.sockets.emit('test',  JSON.parse(message));
    }

    if(channel == 'jackpot.timer') {
        let data = JSON.parse(message);
        JackpotStartTimer(data.min, data.sec, data.time);
        return;
    }

    if(channel == 'jackpot.newBet') {
        let bet = JSON.parse(message);
        io.sockets.emit('jackpot.newBet', bet);
    }

    if(channel == 'crash') {
		return io.sockets.emit('crash', JSON.parse(message));
	}
    
    console.log(channel+":"+message);
});

function JackpotStartTimer(min, sec, time) {
    var preFinish = false;
    var total = time;
    var time = time;
    var timer;
    clearInterval(timer);
    timer = null;
    timer = setInterval(function() {
        time--;
        sec--;
        if(time <= 3) {
            if(!preFinish) {
                preFinish = true;
                JackpotSetStatus(2);
            }
        }
        if(sec == 0) {
            if(min == 0) {
                clearInterval(timer);
                timer = null;
                JackpotGetSlider();
                return;
            }
            min--;
            sec = 60;
        }
        io.sockets.emit('jackpot.timer', {
            min : min,
            sec : sec,
            time : time,
            timer : total
        });
    }, 1000);
}

function JackpotGetSlider() {
    requestify.post('https://donuttrix.ru/api/jackpot/getSlider')
        .then(function(res) {
            res = JSON.parse(res.body);
            io.sockets.emit('jackpot.slider', res);
            ngTimer();
        }, function(res) {
            console.log('Error in getSlider()');
        });
}

function ngTimer() {
    var ngtime = 20;
    clearInterval(ngtimer);
    var ngtimer = setInterval(function() {
        ngtime--;
        io.sockets.emit('jackpot.ngTimer', {
            ngtime : ngtime
        });
        if(ngtime <= 0) {
            clearInterval(ngtimer);
            JackpotNewGame();
        }
    }, 1000);
}

function JackpotNewGame() {
    requestify.post('https://donuttrix.ru/api/jackpot/newGame')
        .then(function(res) {
            res = JSON.parse(res.body);
            io.sockets.emit('jackpot.newGame', res);
        }, function(res) {
            console.log('Error in newGame()');
            setTimeout(JackpotNewGame, 1000);
        });
}

function JackpotGetStatus() {
    requestify.post('https://donuttrix.ru/api/jackpot/getStatus')
        .then(function(res) {
            res = JSON.parse(res.body);
            console.log('Current game #' + res.id)
            if(res.status == 1) JackpotStartTimer(res.room, res.min, res.sec, res.time, res.timer);
            if(res.status == 2) JackpotStartTimer(res.room, res.min, res.sec, res.time, res.timer);
            if(res.status == 3) JackpotNewGame(res.room);
        }, function(res) {
            console.log('Error in getStatus()');
            setTimeout(JackpotGetStatus, 1000);
        });
}

function JackpotSetStatus(status) {
    requestify.post('https://donuttrix.ru/api/jackpot/setStatus', { status : status })
        .then(function(res) {
            res = JSON.parse(res.body);
            console.log(res.msg);
        }, function(res) {
            console.log('Error in setStatus()');
            setTimeout(JackpotGetStatus, 1000, status);
        });
}

function stopWheel() {
    closeBets(); // whatever, leave it as it is.
    setTimeout(()=>restartWheel(),500);
}
function reloadWheel() {
    startWheel('admin');
}
function adminRestart() {
    wheel_timer_server = 15;
    clearInterval(timer_to_start);
}
function startWheel(type) {
    if(type) adminRestart();
    //if(game_started == false) startBets();
    if(timer_to_start == false || type == 'admin') { // check whether the timer is currently active
        wheel_timer_server--; // subtract a second so players do not lose an extra second to the interval :))
        timer_to_start = setInterval(()=>{ // start sending data every second
            if(wheel_timer_server >= 1) { // check the time remaining until the start
                io.sockets.emit('wheel_start', wheel_timer_server); // send the time until the start to the client
                wheel_timer_server--; // subtract one second from the remaining time
            } else rollWheel(); // timer reached 0, start the spin
        }, 1000);
    }
}

function rollWheel() {
    io.sockets.emit('wheel_start', 0);
    closeBets(); // close betting
    clearInterval(timer_to_start); // clear the interval variable
    //setTimeout(() => restartWheel(), 20000); // simulate a new game
    wheel_timer_server = 15;
    timer_to_restart = setInterval(()=>{ // start sending data every second (SPIN..)
        if(wheel_timer_server >= 1) { // check the time remaining until the start

            io.sockets.emit('wheel_roll', {'timer':{'data':wheel_timer_server}, 'roll':{'data':spin}}); // send the time until the start to the client
            wheel_timer_server--; // subtract one second from the remaining time
        } else restartWheel(); // timer reached 0, simulate a new game
    }, 1000);
}

function restartWheel() {
    endBets();
    clearInterval(timer_to_restart);
    timer_to_start = false;
    wheel_timer_server = 15;
    io.sockets.emit('wheel_clear', {'clear':{'data':'clear_all'}, 'last':{'data':last_color}, 'game':{'id': game_id}});
    io.sockets.emit('wheel_start', wheel_timer_server);
}

function closeBets() {
    requestify.post(`https://donuttrix.ru/api/wheel/close`)
        .then(function(res) {
            res = JSON.parse(res.body);
            spin = res.rotate[0];
            last_color = res.rotate[1];
            game_id = res.gameid;
            console.log(game_id);
            return false; // betting is closed

        }, function(res) {
            res = JSON.stringify(res);
            return console.error('[WHEEL] Error!');
        });
}

function openBets() {
    requestify.post(`https://donuttrix.ru/api/wheel/open`)
        .then(function(res) {
            return false; // betting has started
        }, function(res) {
            res = JSON.stringify(res);
            return console.error('[WHEEL] Error while opening betting!');
        });
}

function startBets() {
    requestify.post(`https://donuttrix.ru/api/wheel/start`)
        .then(function(res) {
            return false; // betting has started
        }, function(res) {
            res = JSON.stringify(res);
            return console.error('[WHEEL] Error while starting the game!');
        });
}
function endBets() {
    requestify.post(`https://donuttrix.ru/api/wheel/end`)
        .then(function(res) {
            res = JSON.parse(res.body);

        }, function(res) {
            res = JSON.stringify(res);
            return console.error('[WHEEL] Error while ending the game!');
        });
}


function randomInteger(min, max) {
    // get a random integer between (min-0.5) and (max+0.5)
    let rand = min - 0.5 + Math.random() * (max - min + 1);
    return Math.round(rand);
}