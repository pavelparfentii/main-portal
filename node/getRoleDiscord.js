import axios from "axios";

const BOT_TOKEN = '';

const GUILD_ID = '856828604217425941';
const USER_ID = process.argv[2];


const rolesMap = {
    '1143539187337920662': 'discord_ds_role',
    '1143539291646070834': 'discord_game_role',
    '1128990103117774848': 'discord_ss_role',
    '1142856314389798963': 'discord_da_role',
    '1143539055087337492': 'discord_store_role',
};


async function checkUserRole() {
    try {
        const response = await axios.get(`https://discord.com/api/guilds/${GUILD_ID}/members/${USER_ID}`, {
            headers: {
                Authorization: `Bot ${BOT_TOKEN}`,
            },
        });

        const member = response.data;

        const userRoles = member.roles
            .filter(roleId => rolesMap[roleId])
            .map(roleId => rolesMap[roleId]);

        const output = {
            state: 'success',
            data: userRoles.length > 0 ? userRoles : null, // Output the descriptive role names or null if none are found
        };

        console.log(JSON.stringify(output));
    } catch (error) {
        const errorMessage = {
            state: 'error',
            data: error.response ? error.response.data : error.message,
        };
        console.log(JSON.stringify(errorMessage));
    }
}

checkUserRole();
