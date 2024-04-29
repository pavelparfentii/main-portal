import fetch from 'node-fetch';
// const { GUILD_ID, USER_ID, BOT_TOKEN } = process.env;

const BOT_TOKEN = 'MTAxODg2MDI0OTAxNDM0MTcyNQ.G5I5Xo.1JiPW5XvsrIJBKgItcOJEvPLzhLw-LJsBywS_4';
const GUILD_ID = '856828604217425941';
const USER_ID = process.argv[2];

async function getMemberRoles() {
    const memberUrl = `https://discord.com/api/guilds/${GUILD_ID}/members/${USER_ID}`;

    try {
        const response = await fetch(memberUrl, {
            headers: {
                'Authorization': `Bot ${BOT_TOKEN}`
            }
        });

        if (!response.ok) throw new Error(`Error fetching member: ${response.statusText}`);

        const member = await response.json();
        return member.roles; // Array of role IDs
    } catch (error) {
        const errorMessage = {
            state: 'error',
            data: error.response ? error.response.data : error.message,
        };
        console.log(JSON.stringify(errorMessage));
        // console.error(error);
        return null;
    }
}

async function getRoleNamesByIds(roleIds) {
    const rolesUrl = `https://discord.com/api/guilds/${GUILD_ID}/roles`;

    try {
        const response = await fetch(rolesUrl, {
            headers: {
                'Authorization': `Bot ${BOT_TOKEN}`
            }
        });

        if (!response.ok) throw new Error(`Error fetching roles: ${response.statusText}`);

        const roles = await response.json();
        const memberRoles = roles.filter(role => roleIds.includes(role.id));
        return memberRoles.map(role => ({ id: role.id, name: role.name })); // Array of roles with IDs and names
    } catch (error) {
        const errorMessage = {
            state: 'error',
            data: error.response ? error.response.data : error.message,
        };
        console.log(JSON.stringify(errorMessage));

        // console.error(error);
        return null;
    }
}

// Example usage
async function main() {
    const roleIds = await getMemberRoles();
    if (roleIds) {
        const roles = await getRoleNamesByIds(roleIds);

        const output = {
            state: 'success',
            data: roles.length > 0 ? roles : null, // Output the descriptive role names or null if none are found
        };

        console.log(JSON.stringify(output));

        // console.log(roles); // Log roles with IDs and names
    }
}

main();
