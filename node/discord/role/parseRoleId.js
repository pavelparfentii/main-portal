import axios from "axios";
import dotenv from 'dotenv';

// Initialize dotenv to use environment variables
dotenv.config();

const BOT_TOKEN = process.env.DISCROD_BOT_TOKEN;
const GUILD_ID = '856828604217425941';
const USER_ID = process.argv[2];


async function checkUserRole() {
    try {
        // Get the guild member information
        const response = await axios.get(`https://discord.com/api/guilds/${GUILD_ID}/members/${USER_ID}`, {
            headers: {
                Authorization: `Bot ${BOT_TOKEN}`,
            },
        });

        const member = response.data;

        if(member.roles.length > 0){
            const message = {
                state: 'success',
                data: member.roles
            };
            console.log(JSON.stringify(message));
        }else{
            const errorMessage = {
                state: 'error',
                data: error.message
            };
            console.log(JSON.stringify(errorMessage));
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
