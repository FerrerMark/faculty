<?php
function showNotification($message, $color) {
            echo '<div style="color:white; background-color: ' . $color . '; opacity: 1; transition: opacity 1s;" class="notification" id="notification">';
            echo '<p>' . $message . '</p>';
            echo '</div>';
            echo '<script>
                setTimeout(function() {
                    document.getElementById("notification").style.opacity = "0";
                }, 3000);
                setTimeout(function() {
                    document.getElementById("notification").style.display = "none";
                }, 4000);
            </script>';
        }

        ?>

        <style>
            body{
                position: relative;
            }

            .notification{
                position: absolute;
                top: 0;
                right: 0;
                padding: 4px;
                border-radius: 7px;
            }
        </style>