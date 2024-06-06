import { Client } from 'discord.js';
const client = new Client({ intents: 123 });
import dotenv from 'dotenv';
dotenv.config();

const BOT_TOKEN = process.env.DISCROD_BOT_TOKEN;

// Replace with your guild (server) ID
const GUILD_ID = '856828604217425941';

async function removeRole(discordId, roleId) {
    try {
        await client.login(BOT_TOKEN);

        const guild = await client.guilds.fetch(GUILD_ID);
        const member = await guild.members.fetch(discordId);
        const role = await guild.roles.fetch(roleId);

        if (!member) {
            console.error('Member not found');
            return;
        }

        if (!role) {
            console.error('Role not found');
            return;
        }

        await member.roles.add(role);
        console.log(`Removed role ${role.name} from ${member.user.tag}`);
    } catch (error) {
        console.error('Error removing role:', error);
    } finally {
        client.destroy();
    }
}

// Get arguments from command line
const [discordId, roleId] = process.argv.slice(2);

if (!discordId || !roleId) {
    console.error('Please provide both discord_id and role_id as arguments.');
    process.exit(1);
}

removeRole(discordId, roleId);
