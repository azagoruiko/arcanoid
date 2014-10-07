<!DOCTYPE HTML>
<html>
    <head>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
        <style>
            body {
                margin: 0px;
                padding: 0px;
            }
            canvas {
                border: solid black 1px;
            }
        </style>
    </head>
    <body>
        <canvas id="myCanvas" width="1000" height="700"></canvas>
        <button>Start over</button>
        <script>
            var startTime = (new Date()).getTime();
            var keyDown = false;
            $().ready(function() {
                var keyPressed = '';
                $(window).keydown(function(e){
                    if (keyDown) return;
                    keyDown = true;
                    startTime = (new Date()).getTime();
                    switch (e.keyCode) {
                        case 37:
                            keyPressed = 'left';
                            break;
                        case 39:
                            keyPressed = 'right';
                            break;
                    }
                });
                $(window).keyup(function(e){
                    keyPressed = '';
                    keyDown = false;
                });
                window.requestAnimFrame = (function(callback) {
                    return window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame || window.oRequestAnimationFrame || window.msRequestAnimationFrame ||
                            function(callback) {
                                window.setTimeout(callback, 1000 / 60);
                            };
                })();

                function drawRocket(rocket, context) {
                    context.beginPath();
                    context.rect(rocket.x, rocket.y, rocket.width, rocket.height);
                    context.fillStyle = '#8ED6FF';
                    context.fill();
                    context.lineWidth = rocket.borderWidth;
                    context.strokeStyle = 'black';
                    context.stroke();
                }
                
                function drawBall(ball, context) {
                    context.beginPath();
                    context.arc(ball.x, ball.y, ball.radius, 0, 2 * Math.PI, false);
                    context.fillStyle = 'green';
                    context.fill();
                    context.lineWidth = 1;
                    context.strokeStyle = '#003300';
                    context.stroke();
                }
                
                function drawBricks(bricks, context) {
                    for (var i in bricks) {
                        var brick = bricks[i];
                        context.beginPath();
                        context.rect(brick.x, brick.y, brick.width, brick.height);
                        context.fillStyle = '#FFDAFF';
                        context.fill();
                        context.lineWidth = 1;
                        context.strokeStyle = 'black';
                        context.stroke();
                    }
                }
                
                function drawText(text, canvas, context) {
                    context.font="30px Verdana";
                    var gradient=context.createLinearGradient(0,0,canvas.width,0);
                    gradient.addColorStop("0","magenta");
                    gradient.addColorStop("0.5","blue");
                    gradient.addColorStop("1.0","red");
                    context.fillStyle=gradient;
                    context.fillText(text,canvas.width /2 - 150,canvas.height / 2);
                }
                
                function animate(game, canvas, context) {
                    var linearSpeed = 10;
                    var time = (new Date()).getTime() - startTime;
                    if (keyPressed !== '') {
                        var newX = game.rocket.x;
                        var delta = linearSpeed * time / 1000;
                        if (keyPressed === 'left')
                            newX -= delta;
                        else if (keyPressed === 'right')
                            newX += delta;

                        var rightX = canvas.width - game.rocket.width - game.rocket.borderWidth / 2;
                        var leftX = 0;
                        if (newX < rightX && newX > leftX) {
                            game.rocket.x = newX;
                        }
                    }
                    
                    if (game.breakBrick()) {
                        game.ball.vector.dirY *=-1;
                            if (game.bricks.length === 0) {
                            game.state = 'won';
                            game.ball.vector.dirY = 0;
                            game.ball.vector.dirX = 0;
                        }
                    } else {
                        if (game.ball.y + game.ball.radius >= canvas.height - game.rocket.height 
                                && game.ball.x >= game.rocket.x
                                && game.ball.x <= game.rocket.x + game.rocket.width) {
                            game.ball.vector.angle = Math.abs(game.ball.vector.angle - 2*Math.PI);
                            game.ball.vector.dirY *=-1;
                        }

                        if (game.ball.x <= game.ball.radius || game.ball.x >= canvas.width - game.ball.radius) {
                            game.ball.vector.angle = Math.abs(game.ball.vector.angle - 2*Math.PI);
                            game.ball.vector.dirX *=-1;
                        }

                        if (game.ball.y <= game.ball.radius) {
                            game.ball.vector.angle = Math.abs(game.ball.vector.angle - 2*Math.PI);
                            game.ball.vector.dirY *=-1;
                        }

                        if (game.ball.y >= canvas.height - game.ball.radius) {
                            game.ball.vector.dirY = 0;
                            game.ball.vector.dirX = 0;
                            game.state = 'over';
                        }
                    }
                    game.ball.x += game.ball.vector.dirX * 2;
                    game.ball.y += game.ball.vector.dirY * 2;
                    
                    if (game.bricks.length === 0) {
                        game.state = 'won';
                        game.ball.vector.dirY = 0;
                        game.ball.vector.dirX = 0;
                    }
                    
                    context.clearRect(0, 0, canvas.width, canvas.height);
                    drawRocket(game.rocket, context);
                    drawBricks(game.bricks, context);
                    drawBall(game.ball, context);
                    
                    switch (game.state) {
                        case 'won':
                            drawText("A winner is you!", canvas, context);
                            break;
                        case 'over':
                            drawText("Game over!", canvas, context);
                            break;
                    }
                    
                    // request new frame
                    requestAnimFrame(function() {
                        animate(game, canvas, context);
                    });
                }
                var canvas = $('#myCanvas')[0];
                var context = canvas.getContext('2d');

                var game = {
                    rocket: {},
                    ball: {},
                    state: 'unstarted',
                    bricks: []
                };

                game.rocket = {
                    x: 0,
                    y: canvas.height - 20,
                    width: 100,
                    height: 20,
                    borderWidth: 2
                };
                game.ball = {
                    x: canvas.width / 2,
                    y: canvas.height / 2,
                    radius: 10,
                    vector: {angle: Math.PI, dirX: 1, dirY: -1}
                };
                
                game.breakBrick = function () {
                    for (var i = this.bricks.length -1; i >= 0; i--) {
                        var brick = this.bricks[i];
                        if (this.ballTop() <= brick.y + brick.height
                            && this.ballBottom() > brick.y
                            && this.ballLeft() >= brick.x
                            && this.ballRight() <= brick.x + brick.width) {
                            this.bricks.splice(i,1);
                            return true;
                        }
                    }
                    return false;
                };
                
                game.ballLeft = function () {
                    return this.ball.x - this.ball.radius;
                };
                
                game.ballRight = function () {
                    return this.ball.x + this.ball.radius;
                };
                
                game.ballTop = function () {
                    return this.ball.y - this.ball.radius;
                };
                
                game.ballBottom = function () {
                    return this.ball.y + this.ball.radius;
                };
                
                game.canvas = canvas;
                
                game.fillBricks = function(width) {
                    this.brickWidth = width;
                    this.bricks = [];
                    var bricksPerRow = Math.floor((canvas.width - 10) / (width + 10));
                    var rightGap = (this.canvas.width - (bricksPerRow*width + bricksPerRow*10))/2;
                    for (var i = 0; i < 3; i++) {
                        for (var j = 0; j < bricksPerRow; j++) {
                            this.bricks.push({
                                x: rightGap + j * (width + 10),
                                y: i * 30 + 10,
                                height: 20,
                                width: width
                            });
                        }
                    }
                };
                
                $('button').click(function(){
                    game.fillBricks(game.brickWidth / 2 - 30);
                    game.state = 'started';
                    game.ball.x = canvas.width / 2;
                    game.ball.y = canvas.height / 2;
                    game.ball.vector.dirX = 1;
                    game.ball.vector.dirY = -1;
                });
                
                game.fillBricks(canvas.width - 20);
                drawRocket(game.rocket, context);
                drawBall(game.ball, context);
                drawBricks(game.bricks, context);
                game.state = 'started';
                animate(game, canvas, context);
            });
        </script>
    </body>
</html>  