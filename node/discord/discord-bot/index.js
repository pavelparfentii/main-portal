import { Client, GatewayIntentBits } from 'discord.js';
const client = new Client({
    intents: [
        GatewayIntentBits.Guilds,
        GatewayIntentBits.GuildMembers,
        GatewayIntentBits.GuildPresences,
        GatewayIntentBits.GuildMessages
    ]
});

client.once('ready', () => {
    // console.log(`Logged in as ${client.user.tag}!`);
});

client.on('guildMemberUpdate', (oldMember, newMember) => {
    const oldRoles = oldMember.roles.cache.map(role => role.id);
    const newRoles = newMember.roles.cache.map(role => role.id);

    const addedRoles = newRoles.filter(role => !oldRoles.includes(role));
    const removedRoles = oldRoles.filter(role => !newRoles.includes(role));

    if (addedRoles.length > 0) {
        addedRoles.forEach(role => {
            // console.log(`${newMember.user.tag}(${newMember.user.id}) was given the role ${role}`);

            const output = {
                role: 'addRole',
                discord_id: newMember.user.id,
                role_id: role,
            };
            console.log(JSON.stringify(output));

        });
    }

    if (removedRoles.length > 0) {
        removedRoles.forEach(role => {
            // console.log(`${newMember.user.tag} was removed from the role ${role}`);

            const output = {
                role: 'removeRole',
                discord_id: newMember.user.id,
                role_id: role,
            };
            console.log(JSON.stringify(output));
        });
    }
});

client.login('MTAxODg2MDI0OTAxNDM0MTcyNQ.G5I5Xo.1JiPW5XvsrIJBKgItcOJEvPLzhLw-LJsBywS_4');
