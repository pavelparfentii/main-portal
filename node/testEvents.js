import Web3 from 'web3';
import contractABI from '../node/DAContractABI/contractABI.js';
import dotenv from 'dotenv';
import fetch from "node-fetch";

dotenv.config({ path: './.env' }); // Adjust the path as necessary

async function fetchApiKey() {
    const response = await fetch('https://safesoul.test-dev.site/api/airdrop/infuraKey');
    const data = await response.json();
    return data.key;
}

const web3 = new Web3(`https://mainnet.infura.io/v3/${process.env.INFURA_API_KEY}`);

// const contractAddress = '0x7f36182dee28c45de6072a34d29855bae76dbe2f';
const contractAddress = '0x797a48c46be32aafcedcfd3d8992493d8a1f256b';
const contract = new web3.eth.Contract(contractABI, contractAddress);

async function fetchEventsLastHour() {
    try {
        const latestBlockNumber = await web3.eth.getBlockNumber();
        const blocksPerHour = BigInt(5000); // Approximate blocks per hour, adjust based on actual block time
        const fromBlock = BigInt(latestBlockNumber) - blocksPerHour;
        const toBlock = 'latest';

        const events = await contract.getPastEvents('Transfer', {
            fromBlock: web3.utils.toHex(fromBlock),
            toBlock: web3.utils.toHex(toBlock)
        });

        console.log(`${events.length} events found from the last hour.`);
        events.forEach(event => {
            console.log(event);
            // console.log(event.blockNumber);
            console.log(`From: ${event.returnValues.from} To: ${event.returnValues.to} Token ID: ${event.returnValues.tokenId}`);
        });
    } catch (error) {
        console.error('Error fetching events:', error);
    }
}

fetchEventsLastHour();
