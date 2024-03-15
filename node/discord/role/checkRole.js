// const axios = require('axios');
import axios from "axios";

import dotenv from 'dotenv';

// Initialize dotenv to use environment variables
dotenv.config();

const BOT_TOKEN = process.env.DISCROD_BOT_TOKEN;

const GUILD_ID = '856828604217425941';
const USER_ID = process.argv[2];
const OG_ROLE_ID = '1138407911492702268';
const PATROL_ROLE_ID = '1138407266102554624';
// const BOT_TOKEN = 'your_bot_token'; // Replace with your bot token

async function checkUserRole() {
    try {
        // Get the guild member information
        const response = await axios.get(`https://discord.com/api/guilds/${GUILD_ID}/members/${USER_ID}`, {
            headers: {
                Authorization: `Bot ${BOT_TOKEN}`,
            },
        });

        const member = response.data;


        // Check if the user has the specified role
        const hasRoleOG = member.roles.includes(OG_ROLE_ID);
        const hasRolePatrol = member.roles.includes(PATROL_ROLE_ID);

        if (hasRoleOG) {
            const message = {
                state: 'success',
                data: 'og_patrol'
            };
            console.log(JSON.stringify(message));

        }else if(hasRolePatrol){
            const message = {
                state: 'success',
                data: 'patrol'
            };
            console.log(JSON.stringify(message));

        }
        else {
            const message = {
                state: 'success',
                data: null
            };
            console.log(JSON.stringify(message));

        }

    } catch (error) {
        const errorMessage = {
            state: 'error',
            data: error.message
        };
        console.log(JSON.stringify(errorMessage));


        // console.error('Error:', error.response ? error.response.data : error.message);
    }
}

// Call the function to check the user's role
checkUserRole();
