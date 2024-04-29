import fs from 'fs/promises';
import path from 'path';
import axios from 'axios';
import Web3 from 'web3';
import dotenv from 'dotenv';

dotenv.config({ path: './.env' }); // Adjust the path as necessary

const { INFURA_API_KEY } = process.env;
const web3 = new Web3(new Web3.providers.HttpProvider(`https://mainnet.infura.io/v3/${INFURA_API_KEY}`));

const getEthBalance = async (walletAddress) => {
    try {
        const balanceWei = await web3.eth.getBalance(walletAddress);
        const balanceEth = web3.utils.fromWei(balanceWei, 'ether');
        return parseFloat(balanceEth);
    } catch (error) {
        console.error('Error fetching ETH balance:', error);
        return null;
    }
};

const getEthToUsdRate = async () => {
    try {
        const { data } = await axios.get('https://api.coingecko.com/api/v3/simple/price?ids=ethereum&vs_currencies=usd');
        return data.ethereum.usd;
    } catch (error) {
        console.error('Error fetching ETH to USD rate:', error);
        return null;
    }
};

const convertEthToUsd = async (ethAmount) => {
    const rate = await getEthToUsdRate();
    return ethAmount * rate;
};

const wallet = process.argv[2];

const sendMessages = (message) => {
    console.log(JSON.stringify(message));
};

const getBalanceInUsd = async (walletAddress) => {
    if (!walletAddress) {
        sendMessages({ state: 'error', data: 'No wallet' });
        return;
    }

    const ethBalance = await getEthBalance(walletAddress);
    if (ethBalance !== null) {
        const balanceInUsd = await convertEthToUsd(ethBalance);
        if (balanceInUsd !== null) {
            sendMessages({ state: 'success', data: balanceInUsd });
        } else {
            sendMessages({ state: 'error', data: 'Failed to convert ETH to USD' });
        }
    } else {
        sendMessages({ state: 'error', data: 'Failed to fetch ETH balance' });
    }
};

getBalanceInUsd(wallet);
